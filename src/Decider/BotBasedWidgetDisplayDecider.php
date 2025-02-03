<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Decider;

use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
use Symfony\Component\HttpFoundation\Request;

final class BotBasedWidgetDisplayDecider implements WidgetDisplayDeciderInterface
{
    public function __construct(private readonly BotDetectorInterface $botDetector)
    {
    }

    public function display(Request $request): bool
    {
        return !$this->botDetector->isBotRequest($request);
    }
}
