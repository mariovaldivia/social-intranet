<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Profile::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // IdField::new('id'),
            // Photo or default avatar (templates/admin/field/profile_photo.html.twig)
            TextField::new('imageName', 'profile.field.image')
                ->setTemplatePath('admin/field/profile_photo.html.twig')
                ->setCustomOption('filter', Crud::PAGE_DETAIL === $pageName ? 'user' : 'user_post')
                ->setSortable(false)
                ->hideOnForm(),
            TextField::new('identification'),
            TextField::new('name'),
            TextField::new('lastName'),
            TextField::new('position'),
            AssociationField::new('department'),

            DateField::new('birthDate'),
            DateField::new('hireDate'),
            TextField::new('phone'),
            // Upload field, only on the forms
            TextareaField::new('imageFile')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
        ];
    }

}
