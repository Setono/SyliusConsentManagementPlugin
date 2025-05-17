<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ConsentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        /** @psalm-suppress InvalidArgument */
        return [
            new TwigFunction('sscm_is_granted', [ConsentRuntime::class, 'isGranted']),
            new TwigFunction('sscm_functional_granted', [ConsentRuntime::class, 'functionalGranted']),
            new TwigFunction('sscm_marketing_granted', [ConsentRuntime::class, 'marketingGranted']),
            new TwigFunction('sscm_statistics_granted', [ConsentRuntime::class, 'statisticalGranted']),
            new TwigFunction('sscm_script_tag', [ConsentRuntime::class, 'scriptTag'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_script_tag_attributes', [ConsentRuntime::class, 'scriptTagAttributes'], ['is_safe' => ['html']]),
            new TwigFunction('sscm_consented_categories_json', [ConsentRuntime::class, 'consentedCategoriesJson'], ['is_safe' => ['html']]),
        ];
    }
}
