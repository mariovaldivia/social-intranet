<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Entity\User;
use App\Enum\ActivityStatus;
use App\Enum\ActivityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Activity form of the planning section (EasyAdmin has its own fields).
 */
class SiteActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'label' => 'activity.field.site',
                'placeholder' => 'planning.form.choose_site',
                'group_by' => fn (Site $site) => (string) $site->getCompany(),
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('s')
                    ->innerJoin('s.company', 'c')
                    ->addSelect('c')
                    ->orderBy('c.legalName', 'ASC')
                    ->addOrderBy('s.name', 'ASC'),
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'activity.field.date',
            ])
            ->add('type', EnumType::class, [
                'class' => ActivityType::class,
                'label' => 'activity.field.type',
                'placeholder' => 'planning.form.choose_type',
                // The enum is TranslatableInterface: labels are activity.type.*
                'choice_label' => fn (ActivityType $type) => $type,
            ])
            ->add('status', EnumType::class, [
                'class' => ActivityStatus::class,
                'label' => 'activity.field.status',
                'choice_label' => fn (ActivityStatus $status) => $status,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'activity.field.description',
                'attr' => ['rows' => 4],
            ])
            ->add('assignedUsers', EntityType::class, [
                'class' => User::class,
                'label' => 'activity.field.assigned_users',
                'help' => 'activity.help.assigned_users',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,
                'query_builder' => fn (EntityRepository $repository) => $repository->createQueryBuilder('u')
                    ->leftJoin('u.profile', 'p')
                    ->addSelect('p')
                    ->orderBy('p.name', 'ASC')
                    ->addOrderBy('u.email', 'ASC'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteActivity::class,
        ]);
    }
}
