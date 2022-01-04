<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\CookieCrawler;

use InvalidArgumentException;
use const PHP_URL_HOST;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler as DomCrawler;

final class CookieCrawler implements CookieCrawlerInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function crawl(string $url, int $maxPages = 10, string $strategy = self::STRATEGY_RANDOM): array
    {
        $this->client->restart();

        $baseHost = parse_url($url, PHP_URL_HOST);

        /** @var array<string, string> $pendingUrls */
        $pendingUrls = [$url => $url];

        /** @var array<string, bool> $processedUrls */
        $processedUrls = [];

        do {
            $currentUrl = self::extractElement($pendingUrls, $strategy);

            $crawler = $this->client->request('GET', $currentUrl);

            /**
             * we reverse the array so that the link we see first is the next link that will be crawled
             *
             * @var array<array-key, string> $links
             */
            $links = array_reverse(array_filter($crawler->filter('a')->each(function (DomCrawler $node) use ($baseHost): ?string {
                $link = $node->link()->getUri();
                $host = parse_url($link, PHP_URL_HOST);

                if ($baseHost !== $host) {
                    return null;
                }

                return $link;
            })));

            $processedUrls[$currentUrl] = true;

            foreach ($links as $link) {
                // if the url is null or the url has already been processed or is pending, continue
                if (isset($processedUrls[$link]) || isset($pendingUrls[$link])) {
                    continue;
                }

                // this will unshift an associative array
                // see http://www.mendoweb.be/blog/php-array_unshift-key-array_unshift-associative-array/
                $pendingUrls = [$url => $url] + $pendingUrls;
            }
        } while (count($processedUrls) < $maxPages && count($pendingUrls) > 0);

        $cookies = [];

        foreach ($this->client->getCookieJar()->all() as $cookie) {
            $cookies[] = CrawledCookie::fromBrowserKitCookie($cookie);
        }

        return $cookies;
    }

    /**
     * @return array<array-key, string>
     */
    public static function getStrategies(): array
    {
        return [
            self::STRATEGY_BREADTH_FIRST,
            self::STRATEGY_DEPTH_FIRST,
            self::STRATEGY_RANDOM,
        ];
    }

    /**
     * @param array<string, string> $arr
     */
    private static function extractElement(array &$arr, string $strategy): string
    {
        switch ($strategy) {
            case self::STRATEGY_RANDOM:
                $key = array_rand($arr);
                $val = $arr[$key];
                unset($arr[$key]);

                return $val;
            case self::STRATEGY_BREADTH_FIRST:
                return array_shift($arr);
            case self::STRATEGY_DEPTH_FIRST:
                return array_pop($arr);
        }

        throw new InvalidArgumentException(sprintf('The strategy must be one of [%s]', implode(', ', self::getStrategies())));
    }
}
