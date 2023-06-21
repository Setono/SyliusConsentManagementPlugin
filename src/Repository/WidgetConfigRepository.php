<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Repository;

use Setono\SyliusConsentManagementPlugin\Model\WidgetConfigInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class WidgetConfigRepository extends EntityRepository implements WidgetConfigRepositoryInterface
{
    public function findOneByChannelAndLocale(ChannelInterface $channel, string $locale): ?WidgetConfigInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->join('o.locale', 'l')
            ->andWhere('o.channel = :channel')
            ->andWhere('l.code = :localeCode')
            ->setParameter('channel', $channel)
            ->setParameter('localeCode', $locale)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullorIsInstanceOf($obj, WidgetConfigInterface::class);

        return $obj;
    }
}
