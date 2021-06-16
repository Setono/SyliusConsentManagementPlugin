<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\Consent\Consent;

/**
 * @mixin OrderInterface
 */
trait OrderTrait
{
    protected ?Consent $consent = null;

    public function getConsent(): ?Consent
    {
        return $this->consent;
    }

    public function setConsent(Consent $consent): void
    {
        $this->consent = $consent;
    }
}
