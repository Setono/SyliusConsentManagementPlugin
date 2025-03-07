<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use League\Uri\Contracts\UriInterface;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieFactory implements CookieFactoryInterface
{
    public function __construct(
        private readonly FactoryInterface $decorated,
        private readonly ChannelContextInterface $channelContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function createNew(): CookieInterface
    {
        /** @var CookieInterface $obj */
        $obj = $this->decorated->createNew();
        $obj->setUrl($this->resolveUrl());

        try {
            $obj->addChannel($this->channelContext->getChannel());
        } catch (ChannelNotFoundException) {
        }

        return $obj;
    }

    public function createWithName(string $name): CookieInterface
    {
        $obj = $this->createNew();
        $obj->setName($name);

        return $obj;
    }

    public function createFromSample(mixed $sample): CookieInterface
    {
        if (!is_array($sample)) {
            throw new \InvalidArgumentException('The sample must be an array');
        }

        if (!array_key_exists('name', $sample) || !array_key_exists('expires', $sample)) {
            throw new \InvalidArgumentException('The sample must contain the keys "name" and "expires"');
        }

        if (!is_string($sample['name']) || '' === $sample['name']) {
            throw new \InvalidArgumentException('The sample key "name" must be a non-empty string');
        }

        if (null !== $sample['expires'] && !is_numeric($sample['expires'])) {
            throw new \InvalidArgumentException('The sample key "expires" must be null or numeric');
        }

        $cookie = $this->createNew();
        $cookie->setName($sample['name']);

        if (null === $sample['expires']) {
            $cookie->setSession(true);
        } else {
            $cookie->setTtl(self::timestampToInterval((int) ($sample['expires'] / 1000)));
        }

        return $cookie;
    }

    public function createFromBrowserKitCookie(BrowserKitCookie $cookie, UriInterface|string $url): CookieInterface
    {
        $entity = $this->createNew();
        $entity->setUrl((string) $url);
        $entity->setName($cookie->getName());

        $expires = $cookie->getExpiresTime();

        if (null === $expires) {
            $entity->setSession(true);
        } else {
            $entity->setTtl(self::timestampToInterval((int) $expires));
        }

        return $entity;
    }

    private function resolveUrl(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        if (!$request->isXmlHttpRequest()) {
            return $request->getUri();
        }

        return $request->headers->get('referer');
    }

    private static function timestampToInterval(int $timestamp): \DateInterval
    {
        $now = new \DateTimeImmutable();
        $then = new \DateTimeImmutable('@' . $timestamp);

        return $now->diff($then);
    }
}
