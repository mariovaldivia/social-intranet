<?php

namespace App\Tests\Controller;

use App\Tests\Traits\LogsInUserTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessTest extends WebTestCase
{
    use LogsInUserTrait;

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/login');
    }

    public function testRegularUserIsForbidden(): void
    {
        $client = static::createClient();
        $this->logIn($client, static::getContainer()->get('doctrine')->getManager());

        $client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessDashboard(): void
    {
        $client = static::createClient();
        $this->logIn($client, static::getContainer()->get('doctrine')->getManager(), ['ROLE_ADMIN']);

        $client->request('GET', '/admin');
        // EasyAdmin redirects the dashboard root to the first CRUD page
        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }

        self::assertResponseIsSuccessful();
    }
}
