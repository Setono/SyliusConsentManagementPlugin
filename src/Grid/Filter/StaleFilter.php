<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Grid\Filter;

use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;

final class StaleFilter implements FilterInterface
{
    public function __construct(private readonly string $staleThreshold)
    {
    }

    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        if (!is_string($data) || '' === $data) {
            return;
        }

        $staleThreshold = new \DateTimeImmutable($this->staleThreshold);

        if ('true' === $data) {
            $dataSource->restrict($dataSource->getExpressionBuilder()->lessThanOrEqual('lastSeenAt', $staleThreshold));
        } else {
            $dataSource->restrict($dataSource->getExpressionBuilder()->greaterThan('lastSeenAt', $staleThreshold));
        }
    }
}
