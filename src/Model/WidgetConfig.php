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

    protected ?string $heading = null;

    protected ?string $body = null;

    // todo should be called 'acceptSelected'
    protected ?string $rejectButtonLabel = null;

    // todo should be called 'acceptAll'
    protected ?string $acceptButtonLabel = null;

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

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function setHeading(?string $heading): void
    {
        $this->heading = $heading;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getRejectButtonLabel(): ?string
    {
        return $this->rejectButtonLabel;
    }

    public function setRejectButtonLabel(?string $rejectButtonLabel): void
    {
        $this->rejectButtonLabel = $rejectButtonLabel;
    }

    public function getAcceptButtonLabel(): ?string
    {
        return $this->acceptButtonLabel;
    }

    public function setAcceptButtonLabel(?string $acceptButtonLabel): void
    {
        $this->acceptButtonLabel = $acceptButtonLabel;
    }
}
