<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider\UrlProvider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

abstract class AbstractUrlProvider implements UrlProviderInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        protected readonly UrlGeneratorInterface $urlGenerator,
        /** @var class-string<ChannelInterface> $channelClass */
        private readonly string $channelClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return array<array-key, ChannelInterface>
     */
    protected function getChannels(): array
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
        Assert::isArray($channels);
        Assert::allIsInstanceOf($channels, ChannelInterface::class);
        Assert::notEmpty($channels, 'There must be at least one channel enabled with a default locale');

        return $channels;
    }

    protected function generateUrl(ChannelInterface $channel, string $locale, string $route, array $parameters = []): string
    {
        $parameters['_locale'] = $locale;

        $hostname = $channel->getHostname();
        if (null === $hostname) {
            return $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Generate an https URL on the channel's hostname instead of the request context's host, scheme and port
        $context = $this->urlGenerator->getContext();
        $host = $context->getHost();
        $scheme = $context->getScheme();
        $httpsPort = $context->getHttpsPort();

        $context->setHost($hostname);
        $context->setScheme('https');
        $context->setHttpsPort(443);

        try {
            return $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        } finally {
            $context->setHost($host);
            $context->setScheme($scheme);
            $context->setHttpsPort($httpsPort);
        }
    }
}
