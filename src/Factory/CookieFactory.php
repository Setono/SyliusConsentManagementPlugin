<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Resource\Factory\FactoryInterface;
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
}
