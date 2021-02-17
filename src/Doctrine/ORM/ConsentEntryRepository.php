<?php

declare(strict_types=1);

namespace Setono\SyliusCookieConsentPlugin\Doctrine\ORM;

use Setono\SyliusCookieConsentPlugin\Model\Consent;
use Setono\SyliusCookieConsentPlugin\Repository\ConsentEntryRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ConsentEntryRepository extends EntityRepository implements ConsentEntryRepositoryInterface
{
    public function findConsentFromClientId(string $clientId): ?Consent
    {
        $result = $this->createQueryBuilder('o')
            ->select('NEW Setono\SyliusCookieConsentPlugin\Model\Consent(o.clientId, o.preferences, o.statistics, o.marketing)')
            ->andWhere('o.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($result, Consent::class);

        return $result;
    }
}
