<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface WidgetConfigInterface extends ResourceInterface, ChannelAwareInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getLocale(): ?LocaleInterface;

    public function setLocale(LocaleInterface $locale): void;

    public function getUsageDescription(): ?string;

    public function setUsageDescription(string $usageDescription): void;
}
