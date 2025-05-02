<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class DateExtension extends AbstractExtension
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sscm_format_date_interval', $this->formatDateInterval(...)),
        ];
    }

    public function formatDateInterval(\DateInterval $dateInterval): string
    {
        $ret = [];

        foreach (['y', 'm', 'd', 'h', 'i', 's'] as $unit) {
            $value = (int) $dateInterval->{$unit};
            if (0 === $value) {
                continue;
            }

            $ret[] = sprintf('%d %s', $value, $this->translator->trans('setono_sylius_consent_management.ui.date_interval.' . $unit, ['value' => $value]));
        }

        return implode(', ', $ret);
    }
}
