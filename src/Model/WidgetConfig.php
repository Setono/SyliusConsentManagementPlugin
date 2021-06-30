<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;

class WidgetConfig implements WidgetConfigInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?LocaleInterface $locale = null;

    protected ?ChannelInterface $channel = null;

    protected ?string $usageDescription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getLocale(): ?LocaleInterface
    {
        return $this->locale;
    }

    public function setLocale(?LocaleInterface $locale): void
    {
        $this->locale = $locale;
    }

    public function getUsageDescription(): ?string
    {
        return $this->usageDescription;
    }

    public function setUsageDescription(string $usageDescription): void
    {
        $this->usageDescription = $usageDescription;
    }
}
