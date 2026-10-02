<?php

namespace App\Controller\Admin;

use App\Entity\Photo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PhotoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('gallery.admin.photo_singular')
            ->setEntityLabelInPlural('gallery.admin.photo_plural')
            ->setSearchFields(['caption', 'imageName', 'album.title'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('album')->add('uploadedBy');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        // Preview from the stored file; uploads go through VichImageType
        yield ImageField::new('imageName', 'gallery.photo.field.image')
            ->setBasePath('/upload/gallery')
            ->hideOnForm();
        yield TextareaField::new('imageFile', 'gallery.photo.field.image')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions(['allow_delete' => false])
            ->onlyOnForms()
            ->setRequired(Crud::PAGE_NEW === $pageName);
        yield AssociationField::new('album', 'gallery.photo.field.album');
        yield TextField::new('caption', 'gallery.photo.field.caption');
        yield AssociationField::new('uploadedBy', 'gallery.photo.field.uploaded_by');
        yield DateTimeField::new('createdAt', 'gallery.photo.field.created_at')->hideOnForm();
    }
}
