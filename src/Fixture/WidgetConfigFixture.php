<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/* not final */ class WidgetConfigFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_consent_management_widget_config';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $childNode = $resourceNode->children();
        $childNode->scalarNode('heading')->cannotBeEmpty();
        $childNode->scalarNode('body')->cannotBeEmpty();
        $childNode->scalarNode('channel')->cannotBeEmpty();
        $childNode->scalarNode('locale')->cannotBeEmpty();
    }
}
