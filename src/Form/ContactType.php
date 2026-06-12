<?php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'contact.name',
                'attr' => ['placeholder' => 'contact.name_placeholder'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'contact.email',
                'attr' => ['placeholder' => 'contact.email_placeholder'],
            ])
            ->add('subject', TextType::class, [
                'label' => 'contact.subject',
                'attr' => ['placeholder' => 'contact.subject_placeholder'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'contact.message',
                'attr' => ['rows' => 6, 'placeholder' => 'contact.message_placeholder'],
            ])
            // Honeypot : champ piège invisible. Rempli => bot => on rejette.
            ->add('website', TextType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'class' => 'hp-field',
                ],
                'row_attr' => ['class' => 'hp-field'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
