<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Option « toggle » des champs mot de passe : ajoute un bouton Afficher/Masquer
 * piloté par le contrôleur Stimulus toggle-password.
 * Reprise allégée de symfony/ux-toggle-password (paquet abandonné par Symfony).
 */
final class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public static function getExtendedTypes(): iterable
    {
        return [PasswordType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'toggle' => false,
            'visible_label' => 'form.password_show',
            'hidden_label' => 'form.password_hide',
        ]);

        $resolver->setAllowedTypes('toggle', 'bool');
        $resolver->setAllowedTypes('visible_label', 'string');
        $resolver->setAllowedTypes('hidden_label', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['toggle'] = $options['toggle'];

        if (!$options['toggle']) {
            return;
        }

        // Le bloc de thème « toggle_password_widget » passe avant « password_widget »
        array_splice($view->vars['block_prefixes'], -1, 0, 'toggle_password');

        $attr = $view->vars['attr'];
        $attr['data-controller'] = trim(($attr['data-controller'] ?? '').' toggle-password');
        $attr['data-toggle-password-visible-label-value'] = $this->translator->trans($options['visible_label']);
        $attr['data-toggle-password-hidden-label-value'] = $this->translator->trans($options['hidden_label']);
        $view->vars['attr'] = $attr;
    }
}
