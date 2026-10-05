<?php

namespace App\Tests\Controller;

use App\Entity\Company;
use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Enum\ActivityStatus;
use App\Enum\ActivityType;
use App\Tests\Traits\LogsInUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActivityStatusChangeTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '94.444.444-4';

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

    public function testAssignedUserMarksTheActivityInProgress(): void
    {
        $id = $this->createActivity(assignToMe: true)->getId();

        $crawler = $this->client->request('GET', $this->url($id));
        self::assertCount(1, $crawler->filter('button.activity-start'));
        self::assertCount(1, $crawler->filter('button.activity-cancel'));

        $this->client->submit($crawler->filter('button.activity-start')->form());
        self::assertResponseRedirects($this->url($id));
        self::assertSame(ActivityStatus::InProgress, $this->reload($id)->getStatus());

        // No longer scheduled: the buttons are gone and starting again is refused
        $crawler = $this->client->request('GET', $this->url($id));
        self::assertCount(0, $crawler->filter('.activity-status-actions'));
        $this->client->request('POST', $this->url($id).'/start');
        self::assertResponseStatusCodeSame(403);
    }

    public function testPlannerCancelsWithAReason(): void
    {
        $id = $this->createActivity(roles: ['ROLE_PLANNER'])->getId();

        $crawler = $this->client->request('GET', $this->url($id));
        $form = $crawler->filter('form[name="activity_cancel"]')->form();
        $form['activity_cancel[cancellationReason]'] = 'The client closed the plant that day';
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects($this->url($id));

        $activity = $this->reload($id);
        self::assertSame(ActivityStatus::Cancelled, $activity->getStatus());
        self::assertSame('The client closed the plant that day', $activity->getCancellationReason());

        $crawler = $this->client->request('GET', $this->url($id));
        self::assertStringContainsString('The client closed the plant that day', $crawler->filter('.activity-cancellation')->text());
        self::assertCount(0, $crawler->filter('.activity-status-actions'));
    }

    public function testCancellingRequiresAReason(): void
    {
        $id = $this->createActivity(assignToMe: true)->getId();

        $crawler = $this->client->request('GET', $this->url($id));
        $form = $crawler->filter('form[name="activity_cancel"]')->form();
        $form['activity_cancel[cancellationReason]'] = '   ';
        $crawler = $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Tell why the activity is cancelled.', $crawler->filter('dialog .text-error')->text());
        self::assertSame('true', $crawler->filter('.activity-status-actions')->attr('data-dialog-open-value'), 'The dialog reopens with the error');
        self::assertSame(ActivityStatus::Scheduled, $this->reload($id)->getStatus());
    }

    public function testOtherUsersCannotChangeTheStatus(): void
    {
        $id = $this->createActivity()->getId();

        $this->client->request('POST', $this->url($id).'/start');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $this->url($id).'/cancel');
        self::assertResponseStatusCodeSame(403);
    }

    public function testEditFormRequiresAReasonWhenCancelling(): void
    {
        $id = $this->createActivity(roles: ['ROLE_PLANNER'])->getId();

        $crawler = $this->client->request('GET', sprintf('/planning/%d/edit', $id));
        $form = $crawler->filter('form[name="site_activity"]')->form();
        $form['site_activity[status]'] = ActivityStatus::Cancelled->value;
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(ActivityStatus::Scheduled, $this->reload($id)->getStatus());

        $form['site_activity[cancellationReason]'] = 'Rescheduled for next week';
        $this->client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects($this->url($id));
        self::assertSame(ActivityStatus::Cancelled, $this->reload($id)->getStatus());
    }

    private function createActivity(array $roles = [], bool $assignToMe = false): SiteActivity
    {
        $user = $this->logIn($this->client, $this->manager(), $roles);

        $company = (new Company())->setLegalName('Status Co')->setTaxId(self::TAX_ID);
        $site = (new Site())->setName('Field site');
        $company->addSite($site);
        $activity = (new SiteActivity())
            ->setSite($site)
            ->setDate(new \DateTime('2027-06-01'))
            ->setType(ActivityType::TechnicalVisit)
            ->setDescription('Visit the substation');
        if ($assignToMe) {
            $activity->addAssignedUser($user);
        }
        $this->manager()->persist($company);
        $this->manager()->persist($activity);
        $this->manager()->flush();

        return $activity;
    }

    private function reload(int $id): SiteActivity
    {
        $this->manager()->clear();

        return $this->manager()->getRepository(SiteActivity::class)->find($id);
    }

    private function url(int $id): string
    {
        return sprintf('/activities/%d', $id);
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
