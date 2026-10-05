<?php

namespace App\Controller\Admin;

use App\Entity\SiteActivity;
use App\Enum\ActivityStatus;
use App\Enum\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class SiteActivityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SiteActivity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Labels are translation keys (translations/messages+intl-icu.*.yaml)
        return $crud
            ->setEntityLabelInSingular('activity.admin.singular')
            ->setEntityLabelInPlural('activity.admin.plural')
            ->setSearchFields(['description', 'site.name', 'site.company.legalName', 'site.company.tradeName'])
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('site', 'activity.field.site'))
            ->add(ChoiceFilter::new('type', 'activity.field.type')->setChoices($this->enumChoices(ActivityType::cases(), 'activity.type.')))
            ->add(ChoiceFilter::new('status', 'activity.field.status')->setChoices($this->enumChoices(ActivityStatus::cases(), 'activity.status.')))
            ->add(DateTimeFilter::new('date', 'activity.field.date'))
            ->add(EntityFilter::new('assignedUsers', 'activity.field.assigned_users'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        // Date-only column: render in UTC so it does not move a day (see CLAUDE.md)
        yield DateField::new('date', 'activity.field.date')->setTimezone('UTC');
        yield AssociationField::new('site', 'activity.field.site')->autocomplete();
        // Labels come from the enums (TranslatableInterface)
        yield ChoiceField::new('type', 'activity.field.type');
        yield ChoiceField::new('status', 'activity.field.status')
            // Translatable enum choices are matched by case name
            ->renderAsBadges([
                ActivityStatus::Scheduled->name => 'info',
                ActivityStatus::InProgress->name => 'warning',
                ActivityStatus::Completed->name => 'success',
                ActivityStatus::Cancelled->name => 'secondary',
            ]);
        yield TextareaField::new('description', 'activity.field.description')
            ->setNumOfRows(4)
            ->setMaxLength(80);
        yield TextareaField::new('cancellationReason', 'activity.field.cancellation_reason')
            ->setHelp('activity.help.cancellation_reason')
            ->hideOnIndex();
        yield AssociationField::new('assignedUsers', 'activity.field.assigned_users')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('activity.help.assigned_users');
        yield AssociationField::new('createdBy', 'activity.field.created_by')->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'activity.field.created_at')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'activity.field.updated_at')->onlyOnDetail();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SiteActivity && null === $entityInstance->getCreatedBy()) {
            $entityInstance->setCreatedBy($this->getUser());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /** @param \BackedEnum[] $cases */
    private function enumChoices(array $cases, string $keyPrefix): array
    {
        $choices = [];
        foreach ($cases as $case) {
            $choices[$keyPrefix.$case->value] = $case->value;
        }

        return $choices;
    }
}
