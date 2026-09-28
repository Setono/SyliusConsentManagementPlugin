<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Factory;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface ConsentEntryTypeFactoryInterface
{
    /**
     * Creates a form based on the \Setono\SyliusConsentManagementPlugin\Form\Type\ConsentEntryType
     *
     * @param Request|null $request if null, the main request from the request stack will be used
     *
     * @throws \InvalidArgumentException if no request is given or available from the request stack
     */
    public function createNew(?Request $request = null): FormInterface;
}
