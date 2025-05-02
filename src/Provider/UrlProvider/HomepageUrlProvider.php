<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Webmozart\Assert\Assert;

final class HomepageUrlProvider extends AbstractUrlProvider
{
    public function getUrls(): iterable
    {
        foreach ($this->getChannels() as $channel) {
            $locale = $channel->getDefaultLocale()?->getCode();
            Assert::notNull($locale);

            yield new Url($this->generateUrl($channel, $locale, 'sylius_shop_homepage'), $channel, $locale);
        }
    }
}
