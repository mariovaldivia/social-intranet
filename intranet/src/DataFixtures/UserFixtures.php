<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher){}
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('mvaldivia@gmail.com');
        $plaintextPassword = "admin123";

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);

        $user2 = new User();
        $user2->setEmail('jperez@gmail.com');
        $plaintextPassword = "intranet123";

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user2,
            $plaintextPassword
        );
        $user2->setPassword($hashedPassword);

        $manager->persist($user2);

        $manager->flush();
    }
}
