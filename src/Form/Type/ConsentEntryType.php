<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\CategoryInterface;
use Setono\SyliusConsentManagementPlugin\Model\ConsentEntryInterface;
use Setono\SyliusConsentManagementPlugin\Provider\CategoryProviderInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        private readonly CategoryProviderInterface $categoryProvider,
        string $dataClass,
        array $validationGroups = [],
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = $this->categoryProvider->getCategories();
        $necessaryCategories = array_filter($categories, static fn (CategoryInterface $category): bool => $category->isNecessary());
        $findOneByCode = static function (string $code) use ($categories): ?CategoryInterface {
            foreach ($categories as $category) {
                if ($category->getCode() === $code) {
                    return $category;
                }
            }

            return null;
        };

        $builder
            ->add('consentedCategories', CategoryChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $categories,
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) use ($categories) {
                // Malformed data is left as is: the form rejects it on submit, so the endpoint answers with a 400
                /** @var mixed $data */
                $data = $event->getData();
                if (!is_array($data)) {
                    return;
                }

                $data['consentedCategories'] ??= [];
                if (!is_array($data['consentedCategories'])) {
                    return;
                }

                foreach ($categories as $category) {
                    if ($category->isNecessary() && !in_array((string) $category->getCode(), $data['consentedCategories'], true)) {
                        $data['consentedCategories'][] = $category->getCode();
                    }
                }

                $event->setData($data);
            })
        ;

        $builder->get('consentedCategories')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $categories) use ($necessaryCategories, $findOneByCode): array {
                    /** @var list<CategoryInterface|string> $categories */
                    $categories = $categories ?? [];

                    foreach ($necessaryCategories as $necessaryCategory) {
                        if (!in_array((string) $necessaryCategory->getCode(), $categories, true)) {
                            $categories[] = $necessaryCategory;
                        }
                    }

                    return array_map(static fn (CategoryInterface|string $category) => $category instanceof CategoryInterface ? $category : $findOneByCode($category), $categories);
                },
                function (?array $value): ?array {
                    return $value;
                },
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        // Pages rendered before the url field was removed (e.g. cached by a reverse proxy, or left open in a tab) still
        // post it. Extra fields aren't mapped, so they are ignored instead of making the form invalid
        $resolver->setDefault('allow_extra_fields', true);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_consent_management_consent_entry';
    }
}
