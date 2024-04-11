<?php

namespace App\Test\Controller;

use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/profile/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Profile::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Profile index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'profile[name]' => 'Testing',
            'profile[position]' => 'Testing',
            'profile[birthdate]' => 'Testing',
            'profile[phone]' => 'Testing',
            'profile[lastName]' => 'Testing',
            'profile[identification]' => 'Testing',
            'profile[imageName]' => 'Testing',
            'profile[imageSize]' => 'Testing',
            'profile[updatedAt]' => 'Testing',
            'profile[department]' => 'Testing',
            'profile[user]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Profile();
        $fixture->setName('My Title');
        $fixture->setPosition('My Title');
        $fixture->setBirthdate('My Title');
        $fixture->setPhone('My Title');
        $fixture->setLastName('My Title');
        $fixture->setIdentification('My Title');
        $fixture->setImageName('My Title');
        $fixture->setImageSize('My Title');
        $fixture->setUpdatedAt('My Title');
        $fixture->setDepartment('My Title');
        $fixture->setUser('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Profile');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Profile();
        $fixture->setName('Value');
        $fixture->setPosition('Value');
        $fixture->setBirthdate('Value');
        $fixture->setPhone('Value');
        $fixture->setLastName('Value');
        $fixture->setIdentification('Value');
        $fixture->setImageName('Value');
        $fixture->setImageSize('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setDepartment('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'profile[name]' => 'Something New',
            'profile[position]' => 'Something New',
            'profile[birthdate]' => 'Something New',
            'profile[phone]' => 'Something New',
            'profile[lastName]' => 'Something New',
            'profile[identification]' => 'Something New',
            'profile[imageName]' => 'Something New',
            'profile[imageSize]' => 'Something New',
            'profile[updatedAt]' => 'Something New',
            'profile[department]' => 'Something New',
            'profile[user]' => 'Something New',
        ]);

        self::assertResponseRedirects('/profile/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getPosition());
        self::assertSame('Something New', $fixture[0]->getBirthdate());
        self::assertSame('Something New', $fixture[0]->getPhone());
        self::assertSame('Something New', $fixture[0]->getLastName());
        self::assertSame('Something New', $fixture[0]->getIdentification());
        self::assertSame('Something New', $fixture[0]->getImageName());
        self::assertSame('Something New', $fixture[0]->getImageSize());
        self::assertSame('Something New', $fixture[0]->getUpdatedAt());
        self::assertSame('Something New', $fixture[0]->getDepartment());
        self::assertSame('Something New', $fixture[0]->getUser());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Profile();
        $fixture->setName('Value');
        $fixture->setPosition('Value');
        $fixture->setBirthdate('Value');
        $fixture->setPhone('Value');
        $fixture->setLastName('Value');
        $fixture->setIdentification('Value');
        $fixture->setImageName('Value');
        $fixture->setImageSize('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setDepartment('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/profile/');
        self::assertSame(0, $this->repository->count([]));
    }
}
