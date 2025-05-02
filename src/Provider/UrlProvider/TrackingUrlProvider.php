<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * Some third party tracking scripts will first set cookies when a tracking parameter is present
 */
final class TrackingUrlProvider extends AbstractUrlProvider
{
    /**
     * @param class-string<ChannelInterface> $channelClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        UrlGeneratorInterface $urlGenerator,
        private readonly ProductRepositoryInterface $productRepository,
        string $channelClass,
        /** @var array<string, string> $trackingUrlPatterns */
        private readonly array $trackingUrlPatterns,
    ) {
        parent::__construct($managerRegistry, $urlGenerator, $channelClass);
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
                    yield new Url(
                        $this->generateUrl(
                            $channel,
                            $locale,
                            'sylius_shop_product_show',
                            ['slug' => $product->getTranslation($locale)->getSlug(), $parameter => $value],
                        ),
                        $channel,
                        $locale,
                    );
                }
            }
        }
    }
}
