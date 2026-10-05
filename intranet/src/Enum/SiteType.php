<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Kind of company site. Labels are the site.type.* translation keys, which
 * EasyAdmin and |trans use through TranslatableInterface.
 */
enum SiteType: string implements TranslatableInterface
{
    case Office = 'office';
    case Branch = 'branch';
    case WorkSite = 'worksite';
    case Plant = 'plant';
    case Warehouse = 'warehouse';
    case Other = 'other';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('site.type.'.$this->value, locale: $locale);
    }
}
