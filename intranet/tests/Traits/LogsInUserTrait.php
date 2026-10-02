<?php

namespace App\Tests\Traits;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Like;
use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Every route except /login requires ROLE_USER, so functional tests
 * need an authenticated client.
 */
trait LogsInUserTrait
{
    private function logIn(KernelBrowser $client, EntityManagerInterface $manager, array $roles = []): User
    {
        $email = 'functional-test@example.com';
        $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword('not-used');
        }

        // base.html.twig expects every user to have a profile, as in
        // ProfileController::new
        if (!$user->getProfile()) {
            $profile = new Profile();
            $profile->setName('Functional');
            $profile->setLastName('Test');
            $profile->setIdentification('00000000');
            $profile->setEmail($email);
            $manager->persist($profile);

            $user->setUsername($profile->generateUsername());
            $user->setProfile($profile);
        }
        $user->setRoles($roles);
        $manager->persist($user);
        $manager->flush();

        $client->loginUser($user);

        return $user;
    }

    /**
     * Other suites delete the test user's profile (cascading to the user),
     * which fails on foreign keys if content created by the user remains.
     * Call from tearDown() in tests that create such content.
     */
    private function removeTestUserContent(EntityManagerInterface $manager): void
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'functional-test@example.com']);
        if (!$user) {
            return;
        }

        // Through the ORM rather than DQL so VichUploader deletes the files
        // (albums cascade to their photos)
        foreach ($manager->getRepository(PhotoAlbum::class)->findBy(['createdBy' => $user]) as $album) {
            $manager->remove($album);
        }
        foreach ($manager->getRepository(Photo::class)->findBy(['uploadedBy' => $user]) as $photo) {
            $manager->remove($photo);
        }
        $manager->flush();

        // Children before parents: likes reference posts/comments, comments
        // reference posts, posts reference events
        foreach ([Like::class, Comment::class, Post::class, Event::class] as $class) {
            $manager->createQuery(sprintf('DELETE FROM %s e WHERE e.user = :user', $class))
                ->setParameter('user', $user)
                ->execute();
        }
    }
}
