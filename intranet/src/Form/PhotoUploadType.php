<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Several photos at once for an album. The controller creates one Photo
 * per file. Limits match docker/uploads.ini (10 MB per file, 20 files).
 */
class PhotoUploadType extends AbstractType
{
    public const MAX_FILES = 20;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('photos', FileType::class, [
            'label' => 'gallery.upload.field.photos',
            'multiple' => true,
            'attr' => ['accept' => 'image/jpeg,image/png,image/webp,image/gif'],
            'constraints' => [
                new Count(
                    min: 1,
                    max: self::MAX_FILES,
                    minMessage: 'gallery.photos.min',
                    maxMessage: 'gallery.photos.max',
                ),
                new All([
                    new Image(
                        maxSize: '10M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        mimeTypesMessage: 'gallery.photo.not_image',
                        maxSizeMessage: 'gallery.photo.too_large',
                    ),
                ]),
            ],
        ]);
    }
}
