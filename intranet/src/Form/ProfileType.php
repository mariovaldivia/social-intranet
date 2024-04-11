<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\Profile;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('lastName')
            ->add('identification')
            ->add('position')
            ->add('department', EntityType::class, [
                'class' => Department::class,
                // 'choice_label' => 'id',
            ])

            ->add('birthdate', null, [
                'widget' => 'single_text',
            ])
            ->add('phone')
            ->add('imageFile')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
