<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class Service implements ServiceInterface
{
    use TimestampableTrait;

    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;

        getTranslation as private doGetTranslation;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $category = null;

    /**
     * @var Collection|CookieInterface[]
     *
     * @psalm-var Collection<array-key, CookieInterface>
     */
    protected Collection $cookies;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->cookies = new ArrayCollection();
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_PREFERENCES => self::CATEGORY_PREFERENCES,
            self::CATEGORY_STATISTICS => self::CATEGORY_STATISTICS,
            self::CATEGORY_MARKETING => self::CATEGORY_MARKETING,
        ];
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function setName(string $name): void
    {
        $this->getTranslation()->setName($name);
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(string $description): void
    {
        $this->getTranslation()->setDescription($description);
    }

    /**
     * @return ServiceTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface
    {
        /** @var ServiceTranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): ServiceTranslationInterface
    {
        return new ServiceTranslation();
    }

    public function getCookies(): Collection
    {
        return $this->cookies;
    }

    public function addCookie(CookieInterface $cookie): void
    {
        if (!$this->hasCookie($cookie)) {
            $cookie->setService($this);
            $this->cookies->add($cookie);
        }
    }

    public function removeCookie(CookieInterface $cookie): void
    {
        if ($this->hasCookie($cookie)) {
            $cookie->setService(null);
            $this->cookies->removeElement($cookie);
        }
    }

    public function hasCookie(CookieInterface $cookie): bool
    {
        return $this->cookies->contains($cookie);
    }
}
