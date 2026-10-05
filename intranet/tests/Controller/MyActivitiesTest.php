<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Entity\User;
use App\Enum\ActivityStatus;
use App\Enum\ActivityType;
use App\Tests\Traits\LogsInUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class MyActivitiesTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '97.777.777-7';
    private const OTHER_EMAIL = 'other-activities@example.com';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $me = $this->logIn($this->client, $this->manager());
        $other = (new User())->setEmail(self::OTHER_EMAIL)->setPassword('not-used');
        $this->manager()->persist($other);

        $company = (new Company())->setLegalName('Field Co')->setTaxId(self::TAX_ID);
        $site = (new Site())->setName('North plant')->setCity('Antofagasta');
        $company->addSite($site);
        $this->manager()->persist($company);

        // "Today" as the app sees it (APP_TIMEZONE)
        $today = \DateTime::createFromInterface(static::getContainer()->get(ClockInterface::class)->now());
        foreach ([
            ['Overdue work', '-1 day', ActivityStatus::Scheduled, [$me]],
            ['Done work', '-1 day', ActivityStatus::Completed, [$me]],
            ['Today work', '+0 day', ActivityStatus::InProgress, [$me]],
            ['Cancelled work', '+1 day', ActivityStatus::Cancelled, [$me]],
            ['Not done work', '+3 days', ActivityStatus::NotDone, [$me]],
            ['Upcoming two days', '+2 days', ActivityStatus::Scheduled, [$me]],
            ['Team work', '+5 days', ActivityStatus::Scheduled, [$me, $other]],
            ['Other person work', '+0 day', ActivityStatus::Scheduled, [$other]],
        ] as [$description, $offset, $status, $users]) {
            $activity = (new SiteActivity())
                ->setSite($site)
                ->setDate((clone $today)->modify($offset))
                ->setType(ActivityType::Maintenance)
                ->setStatus($status)
                ->setDescription($description);
            foreach ($users as $user) {
                $activity->addAssignedUser($user);
            }
            $this->manager()->persist($activity);
        }
        $this->manager()->flush();
    }

    protected function tearDown(): void
    {
        $this->manager()->clear();
        // Sites and their activities go with the company
        if ($company = $this->manager()->getRepository(Company::class)->findOneBy(['taxId' => self::TAX_ID])) {
            $this->manager()->remove($company);
        }
        if ($other = $this->manager()->getRepository(User::class)->findOneBy(['email' => self::OTHER_EMAIL])) {
            $this->manager()->remove($other);
        }
        $this->manager()->flush();

        parent::tearDown();
    }

    public function testShowsOverdueAndUpcomingActivitiesOfTheUser(): void
    {
        $crawler = $this->client->request('GET', '/my-activities/');
        self::assertResponseIsSuccessful();

        self::assertSame(['Overdue work'], $this->descriptions($crawler->filter('.activities-overdue')));
        // Soonest first, without cancelled or not done work, or work assigned to others
        self::assertSame(['Today work', 'Upcoming two days', 'Team work'], $this->descriptions($crawler->filter('.activities-upcoming')));

        self::assertStringContainsString('Today', $crawler->filter('.day-heading')->first()->text());
        $team = $crawler->filter('.activity')->reduce(fn ($card) => str_contains($card->text(), 'Team work'));
        self::assertStringContainsString(self::OTHER_EMAIL, $team->text(), 'Shows who else is assigned');
        self::assertStringContainsString('Field Co · North plant', $team->text());
    }

    public function testHistoryShowsPastActivitiesOfTheUser(): void
    {
        $crawler = $this->client->request('GET', '/my-activities/history');
        self::assertResponseIsSuccessful();

        self::assertSame(['Done work', 'Overdue work'], $this->descriptions($crawler));
    }

    /** @return string[] descriptions of this test's activities, in page order */
    private function descriptions(Crawler $root): array
    {
        $ours = ['Overdue work', 'Done work', 'Today work', 'Cancelled work', 'Not done work', 'Upcoming two days', 'Team work', 'Other person work'];
        $all = $root->filter('.activity-description')->each(fn ($node) => trim($node->text()));

        return array_values(array_intersect($all, $ours));
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
