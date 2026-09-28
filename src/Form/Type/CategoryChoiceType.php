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
        // FormView::$vars is untyped in Symfony, hence the local @var tags in this class
        /** @var array{choices: array<ChoiceView>} $vars */
        $vars = $view->vars;

        $necessary = [];

        foreach ($vars['choices'] as $choice) {
            if ($choice->data instanceof Category && $choice->data->isNecessary()) {
                $necessary[] = $choice->data->getCode();
            }
        }

        $vars['necessary'] = $necessary;
        $view->vars = $vars;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array{necessary?: list<string>} $vars */
        $vars = $view->vars;
        $necessary = $vars['necessary'] ?? [];

        foreach ($view->children as $child) {
            /** @var array{value?: mixed} $childVars */
            $childVars = $child->vars;
            if (!in_array($childVars['value'] ?? null, $necessary, true)) {
                continue;
            }

            $childVars['disabled'] = true;
            $child->vars = $childVars;
        }
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
