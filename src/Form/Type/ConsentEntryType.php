<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ConsentEntryType extends AbstractResourceType
{
    /**
     * @param class-string<ConsentEntryInterface> $dataClass
     * @param list<string> $validationGroups
     */
    public function __construct(
        private readonly RepositoryInterface $categoryRepository,
        string $dataClass,
        array $validationGroups = [],
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('consentedCategories', ChoiceType::class, [
                'choices' => $this->categoryRepository->findAll(),
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
                'choice_value' => 'code',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_consent_entry';
    }
}
