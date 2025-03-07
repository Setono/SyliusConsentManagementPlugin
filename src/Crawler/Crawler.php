<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

use Facebook\WebDriver\JavaScriptExecutor;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusConsentManagementPlugin\Event\Crawled;
use Setono\SyliusConsentManagementPlugin\Event\CrawlStarted;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Setono\SyliusConsentManagementPlugin\Factory\PantherClientFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\UrlProvider\UrlProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->options = $resolver->resolve($options);
    }

    public function start(): void
    {
        $client = $this->pantherClientFactory->create();

        $this->eventDispatcher->dispatch(new CrawlStarted($this, $client));

        foreach ($this->urlProvider->getUrls() as $url) {
            $willCrawl = new WillCrawl($this, $client, $url);
            $this->eventDispatcher->dispatch($willCrawl);

            $this->logger->info('Crawling {url}', ['url' => (string) $willCrawl->url]);

            $client->request('GET', (string) $willCrawl->url);

            if ($this->options['wait_for_document_ready'] > 0) {
                $this->logger->info('Will wait a maximum of {delay} seconds document ready', ['delay' => $this->options['wait_for_document_ready']]);

                $client->wait($this->options['wait_for_document_ready'])->until(fn (JavaScriptExecutor $webDriver): mixed => $webDriver->executeScript(
                    'return document.readyState === "complete";',
                ));
            }

            $this->eventDispatcher->dispatch(new Crawled($this, $client, $willCrawl->url));

            if ($this->options['request_delay'] > 0) {
                $this->logger->info('Will wait {delay} seconds before next request', ['delay' => $this->options['request_delay']]);
                sleep($this->options['request_delay']);
            }
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
