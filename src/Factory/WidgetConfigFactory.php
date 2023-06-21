<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class WidgetConfigFactory implements WidgetConfigFactoryInterface
{
    private FactoryInterface $decorated;

    /** @var RepositoryInterface<LocaleInterface> */
    private RepositoryInterface $localeRepository;

    private TranslatorInterface $translator;

    /**
     * @param RepositoryInterface<LocaleInterface> $localeRepository
     */
    public function __construct(
        FactoryInterface $decorated,
        RepositoryInterface $localeRepository,
        TranslatorInterface $translator,
    ) {
        $this->decorated = $decorated;
        $this->localeRepository = $localeRepository;
        $this->translator = $translator;
    }

    public function createNew(): WidgetConfigInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, WidgetConfigInterface::class);

        return $obj;
    }

    public function createFromChannelAndLocale(ChannelInterface $channel, string $localeCode): WidgetConfigInterface
    {
        /** @var LocaleInterface|object|null $locale */
        $locale = $this->localeRepository->findOneBy([
            'code' => $localeCode,
        ]);
        Assert::isInstanceOf($locale, LocaleInterface::class);

        $obj = $this->createNew();
        $obj->setChannel($channel);
        $obj->setLocale($locale);
        $obj->setUsageDescription($this->translator->trans(
            'setono_sylius_consent_management.ui.widget.introduction',
            ['%channel%' => $channel->getName()],
            null,
            $localeCode,
        ));

        return $obj;
    }
}
