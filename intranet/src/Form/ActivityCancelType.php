<?php

namespace App\Form;

use App\Entity\SiteActivity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Cancellation of a scheduled activity from its detail page: only the reason.
 */
class ActivityCancelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cancellationReason', TextareaType::class, [
            'label' => 'activity.field.cancellation_reason',
            'attr' => ['rows' => 3],
            'constraints' => [new NotBlank(message: 'activity.cancellation_reason.not_blank')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteActivity::class,
        ]);
    }
}
