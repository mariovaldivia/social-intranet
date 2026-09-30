<?php

namespace App\Tests\Traits;

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
}
