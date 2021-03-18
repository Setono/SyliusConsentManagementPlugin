<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Doctrine\ORM;

use Setono\ClientId\ClientId;
use Setono\SyliusConsentManagementPlugin\Model\Consent;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\ConsentEntryRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ConsentEntryRepository extends EntityRepository implements ConsentEntryRepositoryInterface
{
    public function findConsentFromClientId(ClientId $clientId): ?Consent
    {
        $result = $this->createQueryBuilder('o')
            ->select('NEW Setono\SyliusConsentManagementPlugin\Model\Consent(o.clientId, o.preferences, o.statistics, o.marketing)')
            ->andWhere('o.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($result, Consent::class);

        return $result;
    }

    public function findOneFromClientId(ClientId $clientId): ?ConsentEntryInterface
    {
        $result = $this->createQueryBuilder('o')
            ->andWhere('o.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($result, ConsentEntryInterface::class);

        return $result;
    }
}
