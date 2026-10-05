<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Progress of a site activity. Labels are the activity.status.* keys.
 */
enum ActivityStatus: string implements TranslatableInterface
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    // Started but could not be finished (with a reason)
    case NotDone = 'not_done';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('activity.status.'.$this->value, locale: $locale);
    }
}
