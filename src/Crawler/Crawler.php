<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler as DomCrawler;

/**
 * todo request each url twice, once with and once without consent
 */
final class Crawler
{
    /** @var array<string, UriInterface> */
    private array $processedUrls = [];

    /** @var array<string, UriInterface> */
    private array $pendingUrls = [];

    private UriInterface $uri;

    private int $pagesToCrawl;

    public function __construct(UriInterface $uri, int $pagesToCrawl)
    {
        $this->uri = $uri;
        $this->pagesToCrawl = $pagesToCrawl;
        $this->pendingUrls[(string) $uri] = $uri;
    }

    public function start(): void
    {
        $client = Client::createChromeClient();

        do {
            $currentUrl = array_shift($this->pendingUrls);

            echo $currentUrl . "\n";
            $crawler = $client->request('GET', (string) $currentUrl);

            /**
             * we reverse the array so that the link we see first is the next link that will be crawled
             *
             * @var array<array-key, UriInterface|null> $urls
             */
            $urls = array_reverse($crawler->filter('a')->each(function (DomCrawler $node): ?UriInterface {
                try {
                    $uri = new Uri($node->link()->getUri());
                } catch (\Throwable $e) {
                    return null;
                }

                if ($this->uri->getHost() !== $uri->getHost()) {
                    return null;
                }

                return $uri;
            }));

            $this->processedUrls[(string) $currentUrl] = $currentUrl;

            foreach ($urls as $url) {
                // if the url is null or the url has already been processed or is pending, continue
                if (null === $url || isset($this->processedUrls[(string) $url]) || isset($this->pendingUrls[(string) $url])) {
                    continue;
                }

                // this will unshift an associative array
                // see http://www.mendoweb.be/blog/php-array_unshift-key-array_unshift-associative-array/
                $this->pendingUrls = [(string) $url => $url] + $this->pendingUrls;
            }
        } while (count($this->processedUrls) < $this->pagesToCrawl && count($this->pendingUrls) > 0);
    }
}
