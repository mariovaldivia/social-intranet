<?php

namespace App\Twig;

use Psr\Clock\ClockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    // From this age on, time_ago falls back to an absolute date
    private const RELATIVE_LIMIT_SECONDS = 7 * 24 * 3600;

    public function __construct(
        private ClockInterface $clock,
        private TranslatorInterface $translator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('time_ago', [$this, 'timeAgo']),
        ];
    }

    /**
     * "5 minutes ago", "3 hours ago", "2 days ago"; dates a week old or more
     * are formatted with $fallbackFormat instead, in the clock's time zone
     * (APP_TIMEZONE) since stored dates are UTC.
     */
    public function timeAgo(\DateTimeInterface $date, string $fallbackFormat = 'd M, H:i'): string
    {
        $now = $this->clock->now();
        $seconds = $now->getTimestamp() - $date->getTimestamp();

        if ($seconds >= self::RELATIVE_LIMIT_SECONDS) {
            return \DateTimeImmutable::createFromInterface($date)
                ->setTimezone($now->getTimezone())
                ->format($fallbackFormat);
        }
        // Future dates (clock drift between servers) count as "just now"
        if ($seconds < 60) {
            return $this->translator->trans('time_ago.just_now');
        }
        if ($seconds < 3600) {
            return $this->translator->trans('time_ago.minutes', ['count' => intdiv($seconds, 60)]);
        }
        if ($seconds < 86400) {
            return $this->translator->trans('time_ago.hours', ['count' => intdiv($seconds, 3600)]);
        }

        return $this->translator->trans('time_ago.days', ['count' => intdiv($seconds, 86400)]);
    }
}
