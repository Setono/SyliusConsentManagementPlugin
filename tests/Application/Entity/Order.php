<?php
declare(strict_types=1);

namespace Tests\Setono\SyliusConsentManagementPlugin\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusConsentManagementPlugin\Model\OrderInterface as SetonoSyliusConsentManagementOrderInterface;
use Setono\SyliusConsentManagementPlugin\Model\OrderTrait as SetonoSyliusConsentManagementOrderTrait;
use Sylius\Component\Core\Model\Order as BaseOrder;

/**
 * @ORM\Entity
 */
class Order extends BaseOrder implements SetonoSyliusConsentManagementOrderInterface
{
    use SetonoSyliusConsentManagementOrderTrait;
}
