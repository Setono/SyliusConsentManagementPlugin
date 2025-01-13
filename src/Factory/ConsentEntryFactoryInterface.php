<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Factory;

use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

interface ConsentEntryFactoryInterface extends FactoryInterface
{
    public function createNew(): ConsentEntryInterface;

    public function createFromRequest(Request $request): ConsentEntryInterface;
}
