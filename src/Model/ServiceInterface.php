<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface ServiceInterface extends ResourceInterface, ChannelsAwareInterface, TimestampableInterface, TranslatableInterface, CodeAwareInterface, \Stringable
{
    public function getId(): ?int;

    public function getCategory(): ?CategoryInterface;

    public function setCategory(?CategoryInterface $category): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    public function getPrivacyPolicyUrl(): ?string;

    public function setPrivacyPolicyUrl(?string $privacyPolicyUrl): void;

    /**
     * @param ChannelInterface|null $channel if the channel is set, the returned collection will only contain cookies with the given channel
     *
     * @return Collection<array-key, CookieInterface>
     */
    public function getCookies(ChannelInterface $channel = null): Collection;

    public function addCookie(CookieInterface $cookie): void;

    public function removeCookie(CookieInterface $cookie): void;

    public function hasCookie(CookieInterface $cookie): bool;
}
