<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
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
            TextField::new('identification'),
            TextField::new('name'),
            TextField::new('lastName'),
            TextField::new('position'),
            AssociationField::new('department'),

            DateField::new('birthDate'),
            TextField::new('phone'),
            TextareaField::new('imageFile')
                ->setFormType(VichImageType::class) // Use VichImageType for handling uploads
                // ->setBasePath('/uploads/users') // Define the base path for displaying images
                // ->onlyOnDetail()
                // ->onlyOnForms(),
            // TextEditorField::new('description'),
        ];
    }
    
}
