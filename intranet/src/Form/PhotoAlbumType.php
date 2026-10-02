<?php

namespace App\Form;

use App\Entity\PhotoAlbum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhotoAlbumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ['label' => 'gallery.album.field.title'])
            ->add('eventDate', null, [
                'widget' => 'single_text',
                'label' => 'gallery.album.field.event_date',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'gallery.album.field.description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhotoAlbum::class,
        ]);
    }
}
