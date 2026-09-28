<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Provider;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusConsentManagementPlugin\Factory\WidgetConfigFactoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Setono\SyliusConsentManagementPlugin\Repository\WidgetConfigRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

final class WidgetConfigProvider implements WidgetConfigProviderInterface
{
    public function __construct(
        private readonly WidgetConfigRepositoryInterface $widgetConfigRepository,
        private readonly WidgetConfigFactoryInterface $widgetConfigFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly ?ManagerRegistry $managerRegistry = null,
    ) {
    }

    public function getWidgetConfig(?ChannelInterface $channel = null, ?string $locale = null): WidgetConfigInterface
    {
        $channel = $channel ?? $this->channelContext->getChannel();
        $locale = $locale ?? $this->localeContext->getLocaleCode();

        $widgetConfig = $this->widgetConfigRepository->findOneByChannelAndLocale($channel, $locale);
        if (null !== $widgetConfig) {
            return $widgetConfig;
        }

        $widgetConfig = $this->widgetConfigFactory->createFromChannelAndLocale($channel, $locale);

        try {
            $this->widgetConfigRepository->add($widgetConfig);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request (typically right after installing or adding a locale) created the config first.
            // The failed flush closed the entity manager, so it is reset before the config created by the other request is read
            $existingWidgetConfig = $this->resetManagerFor($widgetConfig) ? $this->widgetConfigRepository->findOneByChannelAndLocale($channel, $locale) : null;
            if (null === $existingWidgetConfig) {
                throw $e;
            }

            return $existingWidgetConfig;
        }

        return $widgetConfig;
    }

    private function resetManagerFor(object $object): bool
    {
        if (null === $this->managerRegistry) {
            return false;
        }

        $manager = $this->managerRegistry->getManagerForClass($object::class);
        foreach ($this->managerRegistry->getManagers() as $name => $registeredManager) {
            if (null !== $manager && $registeredManager === $manager) {
                $this->managerRegistry->resetManager($name);

                return true;
            }
        }

        return false;
    }
}
