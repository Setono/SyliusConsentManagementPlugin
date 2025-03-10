<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use League\Uri\Uri;
use League\Uri\UriModifier;
use Setono\SyliusConsentManagementPlugin\Checker\RequestBasedConsentChecker;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDecider;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ModifyUrlSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WillCrawl::class => 'modify',
        ];
    }

    public function modify(WillCrawl $event): void
    {
        $event->url->value = Uri::createFromUri(UriModifier::appendQuery($event->url->value, sprintf(
            '%s=1&%s=0',
            RequestBasedConsentChecker::CONSENT_QUERY_PARAM,
            SampleDecider::SAMPLE_QUERY_PARAMETER,
        )));
    }
}
