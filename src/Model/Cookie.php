<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class Cookie implements CookieInterface
{
    use TimestampableTrait;
    use TranslatableTrait {
        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $name = null;

    protected ?string $url = null;

    protected ?string $state = self::STATE_PENDING;

    protected int $samples = 0;

    protected ?string $ttl = null;

    protected bool $session = false;

    protected ?\DateTimeInterface $lastSeenAt = null;

    protected ?ServiceInterface $service = null;

    /** @var Collection<array-key, ChannelInterface> */
    protected Collection $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): void
    {
        $this->getTranslation()->setDescription($description);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return non-empty-list<string>
     */
    public static function getStates(): array
    {
        return [
            self::STATE_PENDING,
            self::STATE_CONFIRMED,
        ];
    }

    public function getSamples(): int
    {
        return $this->samples;
    }

    public function setSamples(int $samples): void
    {
        $this->samples = $samples;
    }

    public function incrementSamples(): void
    {
        ++$this->samples;
    }

    public function getTtl(): ?\DateInterval
    {
        if (null === $this->ttl) {
            return null;
        }

        return new \DateInterval($this->ttl);
    }

    public function setTtl(null|string|\DateInterval $ttl): void
    {
        if ($ttl instanceof \DateInterval) {
            $ttl = $ttl->format('P%yY%mM%dDT%hH%iM%sS');
        }

        $this->ttl = $ttl;
    }

    public function isSession(): bool
    {
        return $this->session;
    }

    public function setSession(bool $session): void
    {
        $this->session = $session;
    }

    public function getLastSeenAt(): ?\DateTimeInterface
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(?\DateTimeInterface $lastSeenAt): void
    {
        $this->lastSeenAt = $lastSeenAt;
    }

    public function getService(): ?ServiceInterface
    {
        return $this->service;
    }

    public function setService(?ServiceInterface $service): void
    {
        $this->service = $service;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }

        $this->service?->addChannel($channel);
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    /**
     * @return CookieTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var CookieTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): CookieTranslationInterface
    {
        return new CookieTranslation();
    }
}
