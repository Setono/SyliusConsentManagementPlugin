<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Fixture;

use Setono\Consent\Consent;
use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/* not final */ class ServiceFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_consent_management_service';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $childNode = $resourceNode->children();
        $childNode->scalarNode('code')->cannotBeEmpty();
        $childNode->enumNode('category')->values(Consent::getAvailableConsents())->cannotBeEmpty();
        $childNode->scalarNode('name')->cannotBeEmpty();
        $childNode->scalarNode('description')->cannotBeEmpty();
        $childNode->variableNode('translations')->cannotBeEmpty()->defaultValue([]);
    }
}
