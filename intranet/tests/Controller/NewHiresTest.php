<?php

namespace App\Tests\Controller;

use App\Entity\Profile;
use App\Tests\Traits\LogsInUserTrait;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewHiresTest extends WebTestCase
{
    use LogsInUserTrait;

    private const IDENTIFICATION_PREFIX = 'hire-test-';

    protected function tearDown(): void
    {
        static::getContainer()->get('doctrine')->getManager()
            ->createQuery('DELETE FROM App\Entity\Profile p WHERE p.identification LIKE :prefix')
            ->setParameter('prefix', self::IDENTIFICATION_PREFIX.'%')
            ->execute();

        parent::tearDown();
    }

    public function testHomeListsLatestHiresMostRecentFirst(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->logIn($client, $manager);

        // "Today" as the app sees it (APP_TIMEZONE)
        $today = \DateTime::createFromInterface(static::getContainer()->get(ClockInterface::class)->now());

        foreach ([
            'Older' => '-40 days',
            'Recent' => '-3 days',
            'Starts today' => '+0 day',
            'Future' => '+5 days',
            'Undated' => null,
        ] as $name => $offset) {
            $profile = new Profile();
            $profile->setName($name);
            $profile->setLastName('Hire');
            $profile->setIdentification(self::IDENTIFICATION_PREFIX.$name);
            $profile->setHireDate($offset ? (clone $today)->modify($offset) : null);
            $manager->persist($profile);
        }
        $manager->flush();

        $crawler = $client->request('GET', '/');
        self::assertResponseIsSuccessful();

        $names = $crawler->filter('.new-hire .font-semibold')->each(fn ($node) => trim($node->text()));
        $ours = array_values(array_filter($names, fn ($name) => str_ends_with($name, ' Hire')));

        self::assertSame(['Starts today Hire', 'Recent Hire', 'Older Hire'], $ours);
        self::assertStringContainsString(
            'Joined '.(clone $today)->modify('-3 days')->format('d M Y'),
            $crawler->filter('.new-hire')->eq(array_search('Recent Hire', $names))->text()
        );
    }
}
