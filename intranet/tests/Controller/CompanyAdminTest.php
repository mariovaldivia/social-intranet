<?php

namespace App\Tests\Controller;

use App\Controller\Admin\CompanyCrudController;
use App\Entity\Company;
use App\Tests\Traits\LogsInUserTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CompanyAdminTest extends WebTestCase
{
    use LogsInUserTrait;

    private const TAX_ID = '76.000.000-0';

    protected function tearDown(): void
    {
        static::getContainer()->get('doctrine')->getManager()
            ->createQuery('DELETE FROM App\Entity\Company c WHERE c.taxId = :taxId')
            ->setParameter('taxId', self::TAX_ID)
            ->execute();

        parent::tearDown();
    }

    public function testAdminCreatesAndListsCompany(): void
    {
        $client = $this->adminClient();

        $this->submitNewCompany($client, [
            'Company[legalName]' => 'Acme Ltda.',
            'Company[tradeName]' => 'Acme',
            'Company[taxId]' => self::TAX_ID,
            'Company[email]' => 'contact@acme.test',
            'Company[city]' => 'Santiago',
            'Company[country]' => 'CL',
        ]);
        self::assertResponseRedirects();

        $company = static::getContainer()->get('doctrine')->getRepository(Company::class)->findOneBy(['taxId' => self::TAX_ID]);
        self::assertNotNull($company);
        self::assertSame('Acme Ltda.', $company->getLegalName());
        self::assertTrue($company->isActive());
        self::assertNotNull($company->getCreatedAt());

        $client->request('GET', $this->url(Crud::PAGE_INDEX));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Acme Ltda.');
    }

    public function testDuplicateTaxIdIsRejected(): void
    {
        $client = $this->adminClient();
        $values = ['Company[legalName]' => 'First', 'Company[taxId]' => self::TAX_ID];

        $this->submitNewCompany($client, $values);
        self::assertResponseRedirects();

        $this->submitNewCompany($client, ['Company[legalName]' => 'Second'] + $values);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name="Company"]', 'A company with this tax ID already exists.');
    }

    public function testRegularUserCannotAccessCompanies(): void
    {
        $client = static::createClient();
        $this->logIn($client, static::getContainer()->get('doctrine')->getManager());

        $client->request('GET', $this->url(Crud::PAGE_INDEX));
        self::assertResponseStatusCodeSame(403);
    }

    private function adminClient(): KernelBrowser
    {
        $client = static::createClient();
        $this->logIn($client, static::getContainer()->get('doctrine')->getManager(), ['ROLE_ADMIN']);

        return $client;
    }

    private function submitNewCompany(KernelBrowser $client, array $values): void
    {
        $crawler = $client->request('GET', $this->url(Crud::PAGE_NEW));
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('button[name="ea[newForm][btn]"][value="saveAndReturn"]')->form();
        // Stateless CSRF validates the Origin header
        $client->submit($form, $values, ['HTTP_ORIGIN' => 'http://localhost']);
    }

    private function url(string $action): string
    {
        return static::getContainer()->get(AdminUrlGenerator::class)
            ->setController(CompanyCrudController::class)
            ->setAction($action)
            ->generateUrl();
    }
}
