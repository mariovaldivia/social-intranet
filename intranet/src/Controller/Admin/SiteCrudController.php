<?php

namespace App\Controller\Admin;

use App\Entity\Site;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use App\Enum\SiteType;

class SiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Site::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Labels are translation keys (translations/messages+intl-icu.*.yaml)
        return $crud
            ->setEntityLabelInSingular('site.admin.singular')
            ->setEntityLabelInPlural('site.admin.plural')
            ->setSearchFields(['name', 'code', 'city', 'company.legalName', 'company.tradeName'])
            ->setDefaultSort(['company' => 'ASC', 'name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $types = [];
        foreach (SiteType::cases() as $type) {
            $types['site.type.'.$type->value] = $type->value;
        }

        return $filters
            ->add(EntityFilter::new('company', 'site.field.company'))
            ->add(ChoiceFilter::new('type', 'site.field.type')->setChoices($types))
            ->add(TextFilter::new('region', 'site.field.region'))
            ->add(BooleanFilter::new('active', 'site.field.active'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();

        yield FormField::addFieldset('site.section.identification');
        yield AssociationField::new('company', 'site.field.company');
        yield TextField::new('name', 'site.field.name');
        // Labels come from SiteType (TranslatableInterface)
        yield ChoiceField::new('type', 'site.field.type');
        yield TextField::new('code', 'site.field.code')->setHelp('site.help.code');

        yield FormField::addFieldset('site.section.location');
        yield TextField::new('address', 'site.field.address')->hideOnIndex();
        yield TextField::new('city', 'site.field.city');
        yield TextField::new('region', 'site.field.region');
        yield CountryField::new('country', 'site.field.country')->hideOnIndex();

        yield FormField::addFieldset('site.section.contact');
        yield TelephoneField::new('phone', 'site.field.phone')->hideOnIndex();
        yield EmailField::new('email', 'site.field.email')->hideOnIndex();

        yield FormField::addFieldset('site.section.status');
        yield BooleanField::new('active', 'site.field.active');
        // Number of activities on the index, their list on the detail page
        yield AssociationField::new('activities', 'site.field.activities')->hideOnForm();
        yield DateTimeField::new('createdAt', 'site.field.created_at')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'site.field.updated_at')->onlyOnDetail();
    }
}
