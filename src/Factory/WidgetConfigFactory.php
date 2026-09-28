<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class WidgetConfigFactory implements WidgetConfigFactoryInterface
{
    public function __construct(
        private readonly FactoryInterface $decorated,
        private readonly RepositoryInterface $localeRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function createNew(): WidgetConfigInterface
    {
        /** @var WidgetConfigInterface $obj */
        $obj = $this->decorated->createNew();

        return $obj;
    }

    public function createFromChannelAndLocale(ChannelInterface $channel, string $localeCode): WidgetConfigInterface
    {
        $locale = $this->localeRepository->findOneBy([
            'code' => $localeCode,
        ]);
        Assert::isInstanceOf($locale, LocaleInterface::class);

        $obj = $this->createNew();
        $obj->setChannel($channel);
        $obj->setLocale($locale);

        $obj->setHeading($this->translator->trans(
            'setono_sylius_consent_management.ui.widget_config_defaults.heading',
            ['%channel%' => $channel->getName()],
            null,
            $localeCode,
        ));

        $obj->setBody($this->translator->trans(
            'setono_sylius_consent_management.ui.widget_config_defaults.body',
            ['%channel%' => $channel->getName()],
            null,
            $localeCode,
        ));

        $obj->setAcceptSelectedButtonLabel($this->translator->trans(
            'setono_sylius_consent_management.ui.widget_config_defaults.accept_selected_button',
            [],
            null,
            $localeCode,
        ));

        $obj->setAcceptAllButtonLabel($this->translator->trans(
            'setono_sylius_consent_management.ui.widget_config_defaults.accept_all_button',
            [],
            null,
            $localeCode,
        ));

        return $obj;
    }
}
