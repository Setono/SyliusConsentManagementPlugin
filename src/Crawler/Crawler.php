<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

use Facebook\WebDriver\Exception\Internal\WebDriverCurlException;
use Facebook\WebDriver\Exception\InvalidSessionIdException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\JavaScriptExecutor;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Event\CrawlStarted;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Setono\SyliusConsentManagementPlugin\Factory\PantherClientFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\UrlProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Panther\Client;

final class Crawler implements CrawlerInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    /** @var array{request_delay: int, wait_for_document_ready: int} */
    private array $options;

    public function __construct(
        private readonly PantherClientFactoryInterface $pantherClientFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlProviderInterface $urlProvider,
        array $options = [],
    ) {
        $this->logger = new NullLogger();

        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                'request_delay' => 1,
                'wait_for_document_ready' => 15,
            ])
            ->setAllowedTypes('request_delay', 'int')
            ->setAllowedTypes('wait_for_document_ready', 'int')
        ;

        /** @var array{request_delay: int, wait_for_document_ready: int} $resolvedOptions */
        $resolvedOptions = $resolver->resolve($options);

        $this->options = $resolvedOptions;
    }

    public function start(): CrawlResult
    {
        $client = $this->pantherClientFactory->create();

        try {
            // Panther starts the browser lazily on the first request. Starting it here makes a browser that won't
            // start abort the crawl, instead of failing every URL
            $client->start();

            return $this->crawl($client);
        } finally {
            try {
                $client->quit();
            } catch (WebDriverException|WebDriverCurlException) {
                // The browser is already gone
            }
        }
    }

    private function crawl(Client $client): CrawlResult
    {
        $this->eventDispatcher->dispatch(new CrawlStarted($this, $client));

        $crawled = $failed = 0;

        foreach ($this->urlProvider->getUrls() as $url) {
            $willCrawl = new WillCrawl($this, $client, $url);
            $this->eventDispatcher->dispatch($willCrawl);

            if ($this->load($client, (string) $willCrawl->url)) {
                ++$crawled;
                $this->eventDispatcher->dispatch(new Crawled($this, $client, $willCrawl->url));
            } else {
                ++$failed;
            }

            if ($this->options['request_delay'] > 0) {
                $this->logger->info('Will wait {delay} seconds before next request', ['delay' => $this->options['request_delay']]);
                sleep($this->options['request_delay']);
            }
        }

        $this->logger->log(0 === $failed ? LogLevel::INFO : LogLevel::WARNING, 'Crawled {crawled} URL(s), {failed} failed', [
            'crawled' => $crawled,
            'failed' => $failed,
        ]);

        return new CrawlResult($crawled, $failed);
    }

    /**
     * Returns false if the URL failed to load, e.g. because the page timed out. That must not abort the whole crawl
     */
    private function load(Client $client, string $url): bool
    {
        $this->logger->info('Crawling {url}', ['url' => $url]);

        try {
            $client->request('GET', $url);

            if ($this->options['wait_for_document_ready'] > 0) {
                $this->logger->info('Will wait a maximum of {delay} seconds document ready', ['delay' => $this->options['wait_for_document_ready']]);

                $client->wait($this->options['wait_for_document_ready'])->until(
                    fn (JavaScriptExecutor $webDriver): mixed => $webDriver->executeScript('return document.readyState === "complete";'),
                    sprintf('The document was not ready after %d seconds', $this->options['wait_for_document_ready']),
                );
            }
        } catch (InvalidSessionIdException|WebDriverCurlException $e) {
            // Without a browser session every following URL would fail too
            throw new \RuntimeException(sprintf('Aborted the crawl, because the browser session was lost while crawling %s. Error was: %s', $url, $e->getMessage()), 0, $e);
        } catch (WebDriverException $e) {
            $this->logger->error('Failed to crawl {url}. Error was: {error}', [
                'url' => $url,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }

        return true;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
