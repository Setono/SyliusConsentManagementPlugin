<?php

declare(strict_types=1);

namespace Setono\SyliusConsentManagementPlugin\Form\Type;

use Setono\SyliusConsentManagementPlugin\Model\Category;
use Setono\SyliusConsentManagementPlugin\Repository\CategoryRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CategoryChoiceType extends AbstractType
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->categoryRepository->findAll(),
            'choice_label' => 'name',
            'choice_value' => 'code',
            'label' => 'setono_sylius_consent_management.form.category_choice.label',
            'placeholder' => 'setono_sylius_consent_management.form.category_choice.placeholder',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $necessary = [];

        /** @var ChoiceView $choice */
        foreach ($view->vars['choices'] as $choice) {
            if ($choice->data instanceof Category && $choice->data->isNecessary()) {
                $necessary[] = $choice->data->getCode();
            }
        }

        $view->vars['necessary'] = $necessary;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var list<string> $necessary */
        $necessary = $view->vars['necessary'] ?? [];

        foreach ($view->children as $child) {
            if (!in_array($child->vars['value'], $necessary, true)) {
                continue;
            }

            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $child->vars['disabled'] = true;
        }
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
