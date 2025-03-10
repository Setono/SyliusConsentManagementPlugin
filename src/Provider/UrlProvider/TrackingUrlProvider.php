<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * Some third party tracking scripts will first set cookies when a tracking parameter is present
 */
final class TrackingUrlProvider implements UrlProviderInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        /** @var class-string<ChannelInterface> $channelClass */
        private readonly string $channelClass,
        /** @var array<string, string> $trackingUrlPatterns */
        private readonly array $trackingUrlPatterns,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getUrls(): iterable
    {
        foreach ($this->getChannels() as $channel) {
            $locale = $channel->getDefaultLocale()?->getCode();
            Assert::notNull($locale);

            /** @var mixed|ProductInterface $product */
            foreach ($this->productRepository->findLatestByChannel($channel, $locale, 1) as $product) {
                Assert::isInstanceOf($product, ProductInterface::class);

                foreach ($this->trackingUrlPatterns as $parameter => $value) {
                    yield new Url($this->urlGenerator->generate(
                        'sylius_shop_product_show',
                        ['slug' => $product->getSlug(), '_locale' => $locale, $parameter => $value],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ), $channel, $locale);
                }
            }
        }
    }

    /**
     * @return array<array-key, ChannelInterface>
     */
    private function getChannels(): array
    {
        // todo: Dispatch event with this $qb
        $qb = $this
            ->getManager($this->channelClass)
            ->createQueryBuilder()
            ->select('c')
            ->from($this->channelClass, 'c')
            ->andWhere('c.defaultLocale IS NOT NULL')
            ->andWhere('c.enabled = true')
        ;

        $channels = $qb->getQuery()->getResult();
        Assert::allIsInstanceOf($channels, ChannelInterface::class);
        Assert::notEmpty($channels, 'There must be at least one channel enabled with a default locale');

        return $channels;
    }
}
