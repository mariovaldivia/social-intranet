<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Kind of work done at a site. Labels are the activity.type.* translation
 * keys (TranslatableInterface, used by EasyAdmin and |trans).
 */
enum ActivityType: string implements TranslatableInterface
{
    case TechnicalVisit = 'technical_visit';
    case Maintenance = 'maintenance';
    case Installation = 'installation';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Emergency = 'emergency';
    case Training = 'training';
    case Other = 'other';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('activity.type.'.$this->value, locale: $locale);
    }
}
