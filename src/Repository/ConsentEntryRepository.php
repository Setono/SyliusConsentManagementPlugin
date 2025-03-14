<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\Client\Client;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ConsentEntryRepository extends EntityRepository implements ConsentEntryRepositoryInterface
{
    public function findOneFromClient(Client $client): ?ConsentEntryInterface
    {
        $result = $this->createQueryBuilder('o')
            ->andWhere('o.clientId = :clientId')
            ->setParameter('clientId', $client->id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($result, ConsentEntryInterface::class);

        return $result;
    }
}
