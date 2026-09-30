<?php

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

class AppExtensionTest extends KernelTestCase
{
    private const NOW = '2026-09-30 15:00:00';

    /**
     * @dataProvider timeAgoProvider
     */
    public function testTimeAgo(string $date, string $expected): void
    {
        self::bootKernel();
        $extension = new AppExtension(new MockClock(self::NOW), static::getContainer()->get('translator'));

        self::assertSame($expected, $extension->timeAgo(new \DateTimeImmutable($date)));
    }

    public static function timeAgoProvider(): iterable
    {
        yield 'seconds ago' => ['2026-09-30 14:59:30', 'just now'];
        yield 'future date' => ['2026-09-30 15:05:00', 'just now'];
        yield 'one minute' => ['2026-09-30 14:59:00', '1 minute ago'];
        yield 'minutes' => ['2026-09-30 14:35:00', '25 minutes ago'];
        yield 'one hour' => ['2026-09-30 14:00:00', '1 hour ago'];
        yield 'hours' => ['2026-09-30 03:00:00', '12 hours ago'];
        yield 'one day' => ['2026-09-29 15:00:00', '1 day ago'];
        yield 'rounds down' => ['2026-09-24 15:00:01', '5 days ago'];
        yield 'six days' => ['2026-09-24 15:00:00', '6 days ago'];
        yield 'one second short of a week' => ['2026-09-23 15:00:01', '6 days ago'];
        yield 'a week ago keeps the date' => ['2026-09-23 15:00:00', '23 Sep, 15:00'];
        yield 'older keeps the date' => ['2026-01-05 09:30:00', '05 Jan, 09:30'];
    }
}
