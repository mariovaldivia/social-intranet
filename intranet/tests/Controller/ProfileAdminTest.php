<?php

namespace App\Tests\Controller;

use App\Controller\Admin\ProfileCrudController;
use App\Entity\Profile;
use App\Tests\Traits\LogsInUserTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileAdminTest extends WebTestCase
{
    use LogsInUserTrait;

    private const IDENTIFICATION = 'photo-admin-test';

    protected function tearDown(): void
    {
        static::getContainer()->get('doctrine')->getManager()
            ->createQuery('DELETE FROM App\Entity\Profile p WHERE p.identification = :identification')
            ->setParameter('identification', self::IDENTIFICATION)
            ->execute();

        parent::tearDown();
    }

    public function testIndexShowsProfilePhotoOrDefaultAvatar(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        // The test user's profile has no photo
        $this->logIn($client, $manager, ['ROLE_ADMIN']);

        $withPhoto = new Profile();
        $withPhoto->setName('With');
        $withPhoto->setLastName('Photo');
        $withPhoto->setIdentification(self::IDENTIFICATION);
        $withPhoto->setImageName('with-photo.jpg');
        $manager->persist($withPhoto);
        $manager->flush();

        $crawler = $client->request('GET', $this->url(Crud::PAGE_INDEX));
        self::assertResponseIsSuccessful();

        $photo = $crawler->filter('img[title="With Photo"]');
        self::assertCount(1, $photo);
        self::assertStringContainsString('/user_post/upload/users/with-photo.jpg', $photo->attr('src'));

        $default = $crawler->filter('img[title="Functional Test"]');
        self::assertCount(1, $default);
        self::assertStringContainsString('/user_post/images/user-default.jpg', $default->attr('src'));

        $crawler = $client->request('GET', $this->url(Crud::PAGE_DETAIL, $withPhoto->getId()));
        self::assertStringContainsString('/user/upload/users/with-photo.jpg', $crawler->filter('img[title="With Photo"]')->attr('src'));
    }

    private function url(string $action, ?int $id = null): string
    {
        $generator = static::getContainer()->get(AdminUrlGenerator::class)
            ->setController(ProfileCrudController::class)
            ->setAction($action);
        if ($id) {
            $generator->setEntityId($id);
        }

        return $generator->generateUrl();
    }
}
