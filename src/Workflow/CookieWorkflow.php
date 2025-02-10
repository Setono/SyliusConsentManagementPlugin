<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Workflow;

use Setono\SyliusConsentManagementPlugin\Model\CookieInterface;
use Symfony\Component\Workflow\Transition;

final class CookieWorkflow
{
    private const PROPERTY_NAME = 'state';

    final public const NAME = 'setono_sylius_consent_management__cookie';

    final public const TRANSITION_CONFIRM = 'confirm';

    private function __construct()
    {
    }

    /**
     * @return non-empty-list<string>
     */
    public static function getStates(): array
    {
        return [
            CookieInterface::STATE_PENDING,
            CookieInterface::STATE_CONFIRMED,
        ];
    }

    public static function getConfig(): array
    {
        $transitions = [];
        foreach (self::getTransitions() as $transition) {
            $transitions[$transition->getName()] = [
                'from' => $transition->getFroms(),
                'to' => $transition->getTos(),
            ];
        }

        return [
            self::NAME => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'method',
                    'property' => self::PROPERTY_NAME,
                ],
                'supports' => CookieInterface::class,
                'initial_marking' => CookieInterface::STATE_PENDING,
                'places' => self::getStates(),
                'transitions' => $transitions,
            ],
        ];
    }

    /**
     * @return non-empty-list<Transition>
     */
    public static function getTransitions(): array
    {
        return [
            new Transition(
                self::TRANSITION_CONFIRM,
                CookieInterface::STATE_PENDING,
                CookieInterface::STATE_CONFIRMED,
            ),
        ];
    }
}
