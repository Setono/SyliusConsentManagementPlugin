<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * TODO: This form could most likely be improved, but I am Symfony Form newb. If you see this and you're a Symfony Form oracle, please create a PR <3
 */
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

        $builder->get('consentedCategories')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $categories): array {
                    if (null === $categories) {
                        return [];
                    }

                    return array_map(fn (string $category) => $this->categoryRepository->findOneBy(['code' => $category]), $categories);
                },
                function (?array $value): ?array {
                    return $value;
                },
            ))
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_consent_entry';
    }
}
