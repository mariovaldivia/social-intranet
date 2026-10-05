<?php

namespace App\Tests\Controller;

use App\Controller\Admin\SiteCrudController;
use App\Entity\Company;
use App\Entity\Site;
use App\Enum\SiteType;
use App\Tests\Traits\LogsInUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteAdminTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID_PREFIX = '99.999.99';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn($this->client, $this->manager(), ['ROLE_ADMIN']);
    }

    protected function tearDown(): void
    {
        // Through the ORM: sites go with their company
        foreach ($this->manager()->getRepository(Company::class)->findAll() as $company) {
            if (str_starts_with((string) $company->getTaxId(), self::TAX_ID_PREFIX)) {
                $this->manager()->remove($company);
            }
        }
        $this->manager()->flush();

        parent::tearDown();
    }

    public function testAdminCreatesSiteForCompany(): void
    {
        $company = $this->createCompany('1-1', 'Minera Norte');

        $this->submitNewSite([
            'Site[company]' => (string) $company->getId(),
            'Site[name]' => 'Faena Los Andes',
            'Site[code]' => 'FLA',
            'Site[city]' => 'Calama',
        ], 'Work site');
        self::assertResponseRedirects();

        $this->manager()->clear();
        $site = $this->manager()->getRepository(Site::class)->findOneBy(['code' => 'FLA']);
        self::assertNotNull($site);
        self::assertSame(SiteType::WorkSite, $site->getType());
        self::assertSame('Minera Norte', $site->getCompany()->getLegalName());
        self::assertTrue($site->isActive());

        $crawler = $this->client->request('GET', $this->url(Crud::PAGE_INDEX));
        self::assertResponseIsSuccessful();
        $row = $crawler->filter('table tbody tr')->reduce(fn ($tr) => str_contains($tr->text(), 'Faena Los Andes'));
        self::assertStringContainsString('Work site', $row->text(), 'Type label is translated');
    }

    public function testCodeIsUniqueWithinACompanyOnly(): void
    {
        $first = $this->createCompany('2-2', 'First');
        $second = $this->createCompany('3-3', 'Second');
        $this->createSite($first, 'Main office', 'HQ');

        $this->submitNewSite(['Site[company]' => (string) $first->getId(), 'Site[name]' => 'Other', 'Site[code]' => 'HQ']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name="Site"]', 'This company already has a site with this code.');

        $this->submitNewSite(['Site[company]' => (string) $second->getId(), 'Site[name]' => 'Main office', 'Site[code]' => 'HQ']);
        self::assertResponseRedirects();
    }

    public function testDeletingACompanyDeletesItsSites(): void
    {
        $company = $this->createCompany('4-4', 'Closing');
        $siteId = $this->createSite($company, 'Warehouse', null)->getId();

        $this->manager()->remove($company);
        $this->manager()->flush();

        self::assertNull($this->manager()->getRepository(Site::class)->find($siteId));
    }

    private function submitNewSite(array $values, ?string $typeLabel = null): void
    {
        $crawler = $this->client->request('GET', $this->url(Crud::PAGE_NEW));
        self::assertResponseIsSuccessful();

        if ($typeLabel) {
            // Option values are EasyAdmin's encoding of the enum: pick by label
            $option = $crawler->filter('select[name="Site[type]"] option')->reduce(fn ($o) => trim($o->text()) === $typeLabel);
            $values['Site[type]'] = $option->attr('value');
        }

        $form = $crawler->filter('button[name="ea[newForm][btn]"][value="saveAndReturn"]')->form();
        // Stateless CSRF validates the Origin header
        $this->client->submit($form, $values, ['HTTP_ORIGIN' => 'http://localhost']);
    }

    private function createCompany(string $suffix, string $name): Company
    {
        $company = (new Company())->setLegalName($name)->setTaxId(self::TAX_ID_PREFIX.$suffix);
        $this->manager()->persist($company);
        $this->manager()->flush();

        return $company;
    }

    private function createSite(Company $company, string $name, ?string $code): Site
    {
        $site = (new Site())->setName($name)->setCode($code)->setType(SiteType::Office);
        $company->addSite($site);
        $this->manager()->persist($site);
        $this->manager()->flush();

        return $site;
    }

    private function url(string $action): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setController(SiteCrudController::class)
            ->setAction($action)
            ->generateUrl();
    }

    private function manager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
