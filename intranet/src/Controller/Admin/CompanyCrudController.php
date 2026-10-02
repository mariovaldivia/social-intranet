<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Intl\Countries;

class CompanyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Labels are translation keys (translations/messages+intl-icu.*.yaml)
        return $crud
            ->setEntityLabelInSingular('company.admin.singular')
            ->setEntityLabelInPlural('company.admin.plural')
            ->setSearchFields(['legalName', 'tradeName', 'taxId', 'email', 'city'])
            ->setDefaultSort(['legalName' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('active', 'company.field.active'))
            ->add(ChoiceFilter::new('country', 'company.field.country')->setChoices(array_flip(Countries::getNames())))
            ->add(TextFilter::new('city', 'company.field.city'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();

        yield FormField::addFieldset('company.section.identification');
        yield TextField::new('legalName', 'company.field.legal_name');
        yield TextField::new('tradeName', 'company.field.trade_name');
        yield TextField::new('taxId', 'company.field.tax_id')->setHelp('company.help.tax_id');
        yield TextField::new('industry', 'company.field.industry')->hideOnIndex();

        yield FormField::addFieldset('company.section.contact');
        yield EmailField::new('email', 'company.field.email')->hideOnIndex();
        yield TelephoneField::new('phone', 'company.field.phone');
        yield UrlField::new('website', 'company.field.website')->hideOnIndex();

        yield FormField::addFieldset('company.section.address');
        yield TextField::new('address', 'company.field.address')->hideOnIndex();
        yield TextField::new('city', 'company.field.city');
        yield TextField::new('region', 'company.field.region')->hideOnIndex();
        yield CountryField::new('country', 'company.field.country')->hideOnIndex();
        yield TextField::new('postalCode', 'company.field.postal_code')->hideOnIndex();

        yield FormField::addFieldset('company.section.status');
        yield BooleanField::new('active', 'company.field.active');
        yield DateTimeField::new('createdAt', 'company.field.created_at')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'company.field.updated_at')->onlyOnDetail();
    }
}
