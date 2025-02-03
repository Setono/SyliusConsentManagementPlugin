<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider;

use Symfony\Component\HttpFoundation\Request;

final class CachedWidgetDisplayDecider implements WidgetDisplayDeciderInterface
{
    private ?bool $decision = null;

    public function __construct(private readonly WidgetDisplayDeciderInterface $decorated)
    {
    }

    public function display(Request $request): bool
    {
        if (null === $this->decision) {
            $this->decision = $this->decorated->display($request);
        }

        return $this->decision;
    }
}
