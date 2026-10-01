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
            ->add('name', null, ['label' => 'profile.field.name'])
            ->add('lastName', null, ['label' => 'profile.field.last_name'])
            ->add('identification', null, ['label' => 'profile.field.identification'])
            ->add('position', null, ['label' => 'profile.field.position'])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'profile.field.department',
                // 'choice_label' => 'id',
            ])
            ->add('email', null, ['label' => 'profile.field.email'])
            ->add('birthdate', null, [
                'widget' => 'single_text',
                'label' => 'profile.field.birthdate',
            ])
            ->add('hireDate', null, [
                'widget' => 'single_text',
                'label' => 'profile.field.hire_date',
            ])
            ->add('phone', null, ['label' => 'profile.field.phone'])
            ->add('imageFile', null, ['label' => 'profile.field.image'])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
