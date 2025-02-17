<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Webmozart\Assert\Assert;

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
        private readonly CategoryRepositoryInterface $categoryRepository,
        string $dataClass,
        array $validationGroups = [],
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', HiddenType::class)
            ->add('consentedCategories', CategoryChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
                /** @var mixed $data */
                $data = $event->getData();
                Assert::isArray($data);

                if (!isset($data['consentedCategories'])) {
                    $data['consentedCategories'] = [];
                }
                Assert::isArray($data['consentedCategories']);

                foreach ($this->categoryRepository->findAll() as $category) {
                    if ($category->isNecessary() && !in_array((string) $category->getCode(), $data['consentedCategories'], true)) {
                        $data['consentedCategories'][] = $category->getCode();
                    }
                }

                $event->setData($data);
            })
        ;

        $builder->get('consentedCategories')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $categories): array {
                    if (null === $categories) {
                        return [];
                    }

                    return array_map(fn (string $category) => $this->categoryRepository->findOneByCode($category), $categories);
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
