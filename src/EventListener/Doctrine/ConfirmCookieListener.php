<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Setono\SyliusConsentManagementPlugin\Workflow\CookieWorkflow;
use Symfony\Component\Workflow\WorkflowInterface;

final class ConfirmCookieListener
{
    public function __construct(
        private readonly WorkflowInterface $cookieWorkflow,
        private readonly int $sampleThreshold = 3,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $obj = $eventArgs->getObject();
        if (!$obj instanceof CookieInterface) {
            return;
        }

        if ($obj->getSamples() < $this->sampleThreshold || !$this->cookieWorkflow->can($obj, CookieWorkflow::TRANSITION_CONFIRM)) {
            return;
        }

        $this->cookieWorkflow->apply($obj, CookieWorkflow::TRANSITION_CONFIRM);
    }
}
