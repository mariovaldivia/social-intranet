<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Tests\Traits\LogsInUserTrait;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventListTest extends WebTestCase
{
    use LogsInUserTrait;

    protected function tearDown(): void
    {
        $this->removeTestUserContent(static::getContainer()->get('doctrine')->getManager());

        parent::tearDown();
    }

    public function testIndexListsTodayAndUpcomingEventsSoonestFirst(): void
    {
        $client = $this->createEvents();

        $crawler = $client->request('GET', '/event/');

        self::assertResponseIsSuccessful();
        self::assertSame(['Today event', 'Next week event'], $this->descriptions($crawler));
        self::assertSelectorExists('a[href="/event/past"]');
    }

    public function testPastListsPreviousEventsMostRecentFirst(): void
    {
        $client = $this->createEvents();

        $crawler = $client->request('GET', '/event/past');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Past events');
        self::assertSame(['Yesterday event', 'Last month event'], $this->descriptions($crawler));
        self::assertSelectorExists('a[href="/event/"]');
    }

    private function createEvents(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $user = $this->logIn($client, $manager);

        // Remove events left by other suites so only these are listed
        foreach ($manager->getRepository(Event::class)->findAll() as $event) {
            $manager->remove($event);
        }

        // "Today" as the app sees it (APP_TIMEZONE), not PHP's UTC default
        $today = \DateTime::createFromInterface(static::getContainer()->get(ClockInterface::class)->now());

        foreach ([
            'Last month event' => '-1 month',
            'Yesterday event' => '-1 day',
            'Today event' => '+0 day',
            'Next week event' => '+1 week',
        ] as $description => $offset) {
            $event = new Event();
            $event->setUser($user);
            $event->setDescription($description);
            $event->setDate((clone $today)->modify($offset));
            $manager->persist($event);
        }
        $manager->flush();

        return $client;
    }

    /** @return string[] */
    private function descriptions($crawler): array
    {
        return $crawler->filter('.card-body.post > p')->each(fn ($node) => trim($node->text()));
    }
}
