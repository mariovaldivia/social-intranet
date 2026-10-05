<?php

namespace App\Tests\Controller;

use App\Controller\Admin\SiteActivityCrudController;
use App\Entity\Company;
use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Entity\User;
use App\Enum\ActivityStatus;
use App\Enum\ActivityType;
use App\Tests\Traits\LogsInUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteActivityAdminTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '98.888.888-8';

    private KernelBrowser $client;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->user = $this->logIn($this->client, $this->manager(), ['ROLE_ADMIN']);
    }

    protected function tearDown(): void
    {
        // Through the ORM: sites and their activities go with the company.
        // Start from a clean identity map so stale entities do not interfere
        $this->manager()->clear();
        $company = $this->manager()->getRepository(Company::class)->findOneBy(['taxId' => self::TAX_ID]);
        if ($company) {
            $this->manager()->remove($company);
            $this->manager()->flush();
        }

        parent::tearDown();
    }

    public function testAdminCreatesActivityWithAssignedUsers(): void
    {
        $site = $this->createSite();

        $this->submitNewActivity([
            'SiteActivity[date]' => '2026-10-20',
            'SiteActivity[site][autocomplete]' => (string) $site->getId(),
            'SiteActivity[description]' => 'Preventive maintenance of the generators',
            'SiteActivity[assignedUsers][autocomplete]' => [(string) $this->user->getId()],
        ], 'Maintenance');
        self::assertResponseRedirects();

        $this->manager()->clear();
        $activity = $this->manager()->getRepository(SiteActivity::class)->findOneBy(['site' => $site->getId()]);
        self::assertNotNull($activity);
        self::assertSame(ActivityType::Maintenance, $activity->getType());
        self::assertSame(ActivityStatus::Scheduled, $activity->getStatus(), 'New activities are scheduled');
        self::assertSame('2026-10-20', $activity->getDate()->format('Y-m-d'));
        self::assertSame(['functional-test@example.com'], $activity->getAssignedUsers()->map(fn (User $u) => $u->getEmail())->toArray());
        self::assertSame('functional-test@example.com', $activity->getCreatedBy()?->getEmail(), 'createdBy is the admin who created it');

        $crawler = $this->client->request('GET', $this->url(Crud::PAGE_INDEX));
        $row = $crawler->filter('table tbody tr')->reduce(fn ($tr) => str_contains($tr->text(), 'Preventive maintenance'));
        self::assertStringContainsString('Maintenance', $row->text(), 'Type label is translated');
        self::assertStringContainsString('Scheduled', $row->filter('.badge-info')->text(), 'Status shown as a badge');
    }

    public function testDescriptionIsRequired(): void
    {
        $site = $this->createSite();

        $this->submitNewActivity([
            'SiteActivity[date]' => '2026-10-20',
            'SiteActivity[site][autocomplete]' => (string) $site->getId(),
            'SiteActivity[description]' => '',
        ], 'Inspection');

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->manager()->getRepository(SiteActivity::class)->count(['site' => $site->getId()]));
    }

    public function testDeletingASiteDeletesItsActivities(): void
    {
        $site = $this->createSite();
        $activity = (new SiteActivity())
            ->setSite($site)
            ->setDate(new \DateTime('2026-10-21'))
            ->setType(ActivityType::Repair)
            ->setDescription('Fix the gate')
            ->addAssignedUser($this->user);
        $this->manager()->persist($activity);
        $this->manager()->flush();
        [$activityId, $siteId] = [$activity->getId(), $site->getId()];

        // As in a real request: the site is loaded fresh, with its activities
        $this->manager()->clear();
        $this->manager()->remove($this->manager()->getRepository(Site::class)->find($siteId));
        $this->manager()->flush();
        $this->manager()->clear();

        self::assertNull($this->manager()->getRepository(SiteActivity::class)->find($activityId));
    }

    private function submitNewActivity(array $values, string $typeLabel): void
    {
        $crawler = $this->client->request('GET', $this->url(Crud::PAGE_NEW));
        self::assertResponseIsSuccessful();

        // Option values are EasyAdmin's encoding of the enum: pick by label
        $option = $crawler->filter('select[name="SiteActivity[type]"] option')->reduce(fn ($o) => trim($o->text()) === $typeLabel);
        $values['SiteActivity[type]'] = $option->attr('value');

        $form = $crawler->filter('button[name="ea[newForm][btn]"][value="saveAndReturn"]')->form();
        // Site and users are autocomplete fields ([autocomplete] child): their
        // options load via AJAX, so the form cannot check the submitted ids
        $form->disableValidation();
        // Stateless CSRF validates the Origin header
        $this->client->submit($form, $values, ['HTTP_ORIGIN' => 'http://localhost']);
    }

    private function createSite(): Site
    {
        $company = (new Company())->setLegalName('Activity Test Co')->setTaxId(self::TAX_ID);
        $site = (new Site())->setName('Main plant');
        $company->addSite($site);
        $this->manager()->persist($company);
        $this->manager()->flush();

        return $site;
    }

    private function url(string $action): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setController(SiteActivityCrudController::class)
            ->setAction($action)
            ->generateUrl();
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
