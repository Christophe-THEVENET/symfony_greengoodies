<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'address.label',
                'attr' => ['placeholder' => 'address.label_placeholder'],
            ])
            ->add('line1', TextType::class, [
                'label' => 'address.line1',
                'attr' => ['placeholder' => 'address.line1_placeholder'],
            ])
            ->add('line2', TextType::class, [
                'label' => 'address.line2',
                'required' => false,
                'attr' => ['placeholder' => 'address.line2_placeholder'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'address.postal_code',
            ])
            ->add('city', TextType::class, [
                'label' => 'address.city',
            ])
            ->add('country', TextType::class, [
                'label' => 'address.country',
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'address.is_default',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
