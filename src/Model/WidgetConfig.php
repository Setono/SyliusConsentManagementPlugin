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

    protected ?string $acceptSelectedButtonLabel = null;

    protected ?string $acceptAllButtonLabel = null;

    protected ?array $layout = null;

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

    public function getAcceptSelectedButtonLabel(): ?string
    {
        return $this->acceptSelectedButtonLabel;
    }

    public function setAcceptSelectedButtonLabel(?string $acceptSelectedButtonLabel): void
    {
        $this->acceptSelectedButtonLabel = $acceptSelectedButtonLabel;
    }

    public function getAcceptAllButtonLabel(): ?string
    {
        return $this->acceptAllButtonLabel;
    }

    public function setAcceptAllButtonLabel(?string $acceptAllButtonLabel): void
    {
        $this->acceptAllButtonLabel = $acceptAllButtonLabel;
    }

    public function getLayout(): array
    {
        return $this->layout ?? [];
    }

    public function setLayout(?array $layout): void
    {
        if ([] === $layout) {
            $layout = null;
        }

        $this->layout = $layout;
    }
}
