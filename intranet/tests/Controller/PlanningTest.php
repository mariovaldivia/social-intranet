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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlanningTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '96.666.666-6';

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

    public function testRegularUsersCannotPlan(): void
    {
        $this->logIn($this->client, $this->manager());

        $this->client->request('GET', '/planning/');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/planning/new');
        self::assertResponseStatusCodeSame(403);
    }

    public function testPlannersAndAdminsCanPlan(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        $this->client->request('GET', '/planning/');
        self::assertResponseIsSuccessful();

        $this->logIn($this->client, $this->manager(), ['ROLE_ADMIN']);
        $this->client->request('GET', '/planning/');
        self::assertResponseIsSuccessful();
    }

    public function testCalendarShowsActivitiesOnTheirDayAndFiltersBySite(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        [$north, $south] = $this->createSites();
        $this->createActivity($north, '2027-03-10', 'Check north');
        $this->createActivity($south, '2027-03-10', 'Check south');
        $this->createActivity($north, '2027-04-02', 'Early April work');
        $this->createActivity($north, '2027-04-10', 'Mid April work');

        $crawler = $this->client->request('GET', '/planning/?month=2027-03');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.planning-month', 'March 2027');
        // March 2027 starts on a Monday and ends on a Wednesday: 5 weeks
        self::assertCount(35, $crawler->filter('.planning-day'));
        $day = $crawler->filter('.planning-day[data-date="2027-03-10"]');
        self::assertCount(2, $day->filter('.planning-activity'));
        self::assertMatchesRegularExpression('#^/activities/\d+$#', $day->filter('.planning-activity')->attr('href'), 'Entries open the detail page');
        self::assertStringContainsString('North plant', $day->text());
        // The last week runs into April: its days are shown (dimmed) with their work
        self::assertStringContainsString('North plant', $crawler->filter('.planning-day[data-date="2027-04-02"]')->text());
        self::assertCount(0, $crawler->filter('.planning-day[data-date="2027-04-10"]'), 'Outside the grid');
        self::assertStringNotContainsString('Mid April work', $crawler->filter('.planning-calendar')->html());

        $crawler = $this->client->request('GET', sprintf('/planning/?month=2027-03&site=%d', $north->getId()));
        $day = $crawler->filter('.planning-day[data-date="2027-03-10"]');
        self::assertCount(1, $day->filter('.planning-activity'));
        self::assertStringNotContainsString('South yard', $day->text());
    }

    public function testInvalidMonthFallsBackToTheCurrentMonth(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);

        $this->client->request('GET', '/planning/?month=not-a-month');
        self::assertResponseIsSuccessful();
    }

    public function testCreateActivityFromADayOfTheCalendar(): void
    {
        $user = $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        [$north] = $this->createSites();

        $crawler = $this->client->request('GET', sprintf('/planning/new?date=2027-03-15&site=%d', $north->getId()));
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="site_activity"]')->form();
        self::assertSame('2027-03-15', $form['site_activity[date]']->getValue(), 'Date prefilled from the calendar');
        self::assertSame((string) $north->getId(), $form['site_activity[site]']->getValue(), 'Site prefilled from the filter');

        $form['site_activity[type]'] = ActivityType::Installation->value;
        $form['site_activity[description]'] = 'Install the new sensors';
        $form['site_activity[assignedUsers]'] = [(string) $user->getId()];
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/planning/?month=2027-03');

        $this->manager()->clear();
        $activity = $this->manager()->getRepository(SiteActivity::class)->findOneBy(['description' => 'Install the new sensors']);
        self::assertSame(ActivityType::Installation, $activity->getType());
        self::assertSame(ActivityStatus::Scheduled, $activity->getStatus());
        self::assertSame('2027-03-15', $activity->getDate()->format('Y-m-d'));
        self::assertSame(['functional-test@example.com'], $activity->getAssignedUsers()->map(fn (User $u) => $u->getEmail())->toArray());
        self::assertSame('functional-test@example.com', $activity->getCreatedBy()->getEmail());
    }

    public function testDescriptionIsRequired(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        [$north] = $this->createSites();

        $crawler = $this->client->request('GET', sprintf('/planning/new?site=%d', $north->getId()));
        $form = $crawler->filter('form[name="site_activity"]')->form();
        $form['site_activity[type]'] = ActivityType::Repair->value;
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testEditAndDeleteActivity(): void
    {
        $this->logIn($this->client, $this->manager(), ['ROLE_PLANNER']);
        [$north] = $this->createSites();
        $id = $this->createActivity($north, '2027-03-20', 'To complete')->getId();

        $crawler = $this->client->request('GET', sprintf('/planning/%d/edit', $id));
        $form = $crawler->filter('form[name="site_activity"]')->form();
        $form['site_activity[status]'] = ActivityStatus::Completed->value;
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects(sprintf('/activities/%d', $id), null, 'Back to the detail page');
        $this->manager()->clear();
        self::assertSame(ActivityStatus::Completed, $this->manager()->getRepository(SiteActivity::class)->find($id)->getStatus());

        $crawler = $this->client->request('GET', sprintf('/planning/%d/edit', $id));
        $this->client->submit($crawler->filter(sprintf('form[action="/planning/%d/delete"]', $id))->form());
        self::assertResponseRedirects('/planning/?month=2027-03');
        $this->manager()->clear();
        self::assertNull($this->manager()->getRepository(SiteActivity::class)->find($id));
    }

    /** @return Site[] */
    private function createSites(): array
    {
        $company = (new Company())->setLegalName('Planning Co')->setTaxId(self::TAX_ID);
        $north = (new Site())->setName('North plant');
        $south = (new Site())->setName('South yard');
        $company->addSite($north)->addSite($south);
        $this->manager()->persist($company);
        $this->manager()->flush();

        return [$north, $south];
    }

    private function createActivity(Site $site, string $date, string $description): SiteActivity
    {
        $activity = (new SiteActivity())
            ->setSite($site)
            ->setDate(new \DateTime($date))
            ->setType(ActivityType::Maintenance)
            ->setDescription($description);
        $this->manager()->persist($activity);
        $this->manager()->flush();

        return $activity;
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
