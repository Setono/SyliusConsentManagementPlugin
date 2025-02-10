<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
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

    protected ?\DateTimeInterface $lastSeenAt = null;

    protected ?ServiceInterface $service = null;

    public function __construct()
    {
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

    public function setUrl(string $url): void
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
