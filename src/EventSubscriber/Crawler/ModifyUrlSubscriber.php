<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventSubscriber\Crawler;

use League\Uri\Uri;
use League\Uri\UriModifier;
use Setono\SyliusConsentManagementPlugin\Checker\ConsentOverrideSignerInterface;
use Setono\SyliusConsentManagementPlugin\Decider\Sample\SampleDecider;
use Setono\SyliusConsentManagementPlugin\Event\WillCrawl;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ModifyUrlSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ConsentOverrideSignerInterface $consentOverrideSigner)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WillCrawl::class => 'modify',
        ];
    }

    /**
     * Grants all consents (so all consent-gated scripts run and set their cookies) and disables sampling,
     * because the crawler saves the cookies it finds itself
     */
    public function modify(WillCrawl $event): void
    {
        // The URL is requested right after this event, so the signature only needs to be valid for a short while
        $query = $this->consentOverrideSigner->sign('1', new \DateTimeImmutable('+1 hour'));
        $query[SampleDecider::SAMPLE_QUERY_PARAMETER] = '0';

        $event->url->value = Uri::createFromUri(UriModifier::appendQuery($event->url->value, http_build_query($query)));
    }
}
