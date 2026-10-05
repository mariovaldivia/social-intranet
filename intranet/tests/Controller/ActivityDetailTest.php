<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Enum\ActivityType;
use App\Tests\Traits\LogsInUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActivityDetailTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '95.555.555-5';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $this->manager()->clear();
        // Sites and their activities go with the company
        if ($company = $this->manager()->getRepository(Company::class)->findOneBy(['taxId' => self::TAX_ID])) {
            $this->manager()->remove($company);
            $this->manager()->flush();
        }

        parent::tearDown();
    }

    public function testPlannerSeesDetailWithUpdateButton(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        $activity = $this->createActivity();

        $crawler = $this->client->request('GET', sprintf('/activities/%d', $activity->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Detail Co · Main site');
        self::assertSelectorTextContains('.activity-description', 'Replace the pump');
        self::assertSelectorTextContains('body', 'Copiapó');
        self::assertSame(sprintf('/planning/%d/edit', $activity->getId()), $crawler->filter('a.activity-update')->attr('href'));
        self::assertSelectorExists('a[href="/planning/?month=2027-05"]', 'Back to the calendar month');
    }

    public function testAssignedUserSeesDetailWithoutUpdateButton(): void
    {
        $user = $this->logIn($this->client, $this->manager());
        $activity = $this->createActivity();
        $activity->addAssignedUser($user);
        $this->manager()->flush();

        $crawler = $this->client->request('GET', sprintf('/activities/%d', $activity->getId()));
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('a.activity-update'), 'Only planners can update');
        self::assertStringContainsString('Functional Test', $crawler->filter('.activity-team')->text());
        self::assertSelectorExists('a[href="/my-activities/"]');

        // And cannot reach the edit form either
        $this->client->request('GET', sprintf('/planning/%d/edit', $activity->getId()));
        self::assertResponseStatusCodeSame(403);
    }

    public function testOtherUsersCannotSeeTheActivity(): void
    {
        $this->logIn($this->client, $this->manager());
        $activity = $this->createActivity();

        $this->client->request('GET', sprintf('/activities/%d', $activity->getId()));
        self::assertResponseStatusCodeSame(403);
    }

    public function testMyActivitiesCardsLinkToTheDetail(): void
    {
        $user = $this->logIn($this->client, $this->manager());
        $activity = $this->createActivity('+1 day');
        $activity->addAssignedUser($user);
        $this->manager()->flush();

        $crawler = $this->client->request('GET', '/my-activities/');
        self::assertCount(1, $crawler->filter(sprintf('.activity a[href="/activities/%d"]', $activity->getId())));
    }

    private function createActivity(string $date = '2027-05-12'): SiteActivity
    {
        $company = (new Company())->setLegalName('Detail Co')->setTaxId(self::TAX_ID);
        $site = (new Site())->setName('Main site')->setCity('Copiapó');
        $company->addSite($site);
        $activity = (new SiteActivity())
            ->setSite($site)
            ->setDate(new \DateTime($date))
            ->setType(ActivityType::Repair)
            ->setDescription('Replace the pump');
        $this->manager()->persist($company);
        $this->manager()->persist($activity);
        $this->manager()->flush();

        return $activity;
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
