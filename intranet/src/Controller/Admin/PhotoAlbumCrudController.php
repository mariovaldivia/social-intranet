<?php

namespace App\Controller\Admin;

use App\Entity\PhotoAlbum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PhotoAlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoAlbum::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('gallery.admin.album_singular')
            ->setEntityLabelInPlural('gallery.admin.album_plural')
            ->setSearchFields(['title', 'description'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('title', 'gallery.album.field.title');
        // Date-only column: render in UTC so it does not move a day (see CLAUDE.md)
        yield DateField::new('eventDate', 'gallery.album.field.event_date')->setTimezone('UTC');
        yield TextareaField::new('description', 'gallery.album.field.description')->hideOnIndex();
        yield AssociationField::new('createdBy', 'gallery.album.field.created_by');
        // Shows the number of photos on the index
        yield AssociationField::new('photos', 'gallery.album.field.photos')->onlyOnIndex();
        yield DateTimeField::new('createdAt', 'gallery.album.field.created_at')->hideOnForm();
    }
}
