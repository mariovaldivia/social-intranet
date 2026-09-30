<?php

namespace App\Tests;

use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Dates are stored in UTC (PHP's default time zone) and shown in
 * APP_TIMEZONE; "today" is computed in APP_TIMEZONE.
 */
class TimezoneTest extends KernelTestCase
{
    public function testClockUsesAppTimezone(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        self::assertSame(
            $container->getParameter('app.timezone'),
            $container->get(ClockInterface::class)->now()->getTimezone()->getName()
        );
        self::assertSame('UTC', date_default_timezone_get());
    }

    public function testTwigShowsDatetimesInAppTimezoneAndKeepsDateOnlyValues(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $timezone = new \DateTimeZone($container->getParameter('app.timezone'));
        $twig = $container->get('twig');

        // A DATETIME column hydrated as UTC is converted for display
        $stored = new \DateTime('2026-09-30 15:00:00', new \DateTimeZone('UTC'));
        $expected = (clone $stored)->setTimezone($timezone)->format('d/m/Y H:i');
        $template = $twig->createTemplate("{{ value|date('d/m/Y H:i') }}");
        self::assertSame($expected, $template->render(['value' => $stored]));

        // A DATE column (midnight UTC) must not move to the previous day
        $birthdate = new \DateTime('1987-12-27 00:00:00', new \DateTimeZone('UTC'));
        $template = $twig->createTemplate("{{ value|date('d M', false) }}");
        self::assertSame('27 Dec', $template->render(['value' => $birthdate]));
    }
}
