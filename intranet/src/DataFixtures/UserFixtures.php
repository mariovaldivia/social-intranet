<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Entity\User;
use App\Entity\Profile;
use App\Entity\Department;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher){}
    public function load(ObjectManager $manager): void
    {
        $profile1 = new Profile();
        $profile1->setName('Mario');
        $profile1->setLastName('Valdivia');
        $profile1->setIdentification('111222333');
        $profile1->setPosition('Software Engineer');
        $profile1->setEmail('mvaldivia@gmail.com');
        $profile1->setBirthdate(\DateTime::createFromFormat('m/d/Y', '12/27/1987'));
        $profile1->setHireDate(new \DateTime('-2 years'));
        $profile1->setDepartment(
            $manager->getRepository(Department::class)->findOneByName('TI'));
        $manager->persist($profile1);

        $user = new User();
        $user->setEmail('mvaldivia@gmail.com');
        $user->setUsername($profile1->generateUsername());
        $user->setRoles(['ROLE_ADMIN']);
        $plaintextPassword = "intranet123";

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setProfile($profile1);
        $manager->persist($user);

        $profile2 = new Profile();
        $profile2->setName('Juan');
        $profile2->setLastName('Perez');
        $profile2->setIdentification('33322211');
        $profile2->setPosition('Tecnico');
        $profile2->setEmail('jperez@gmail.com');
        $profile2->setBirthdate(\DateTime::createFromFormat('m/d/Y', '10/15/1995'));
        $profile2->setHireDate(new \DateTime('-10 days'));
        $profile2->setDepartment(
            $manager->getRepository(Department::class)->findOneByName('Producción'));
        $manager->persist($profile2);

        $user2 = new User();
        $user2->setEmail('jperez@gmail.com');
        $user2->setUsername($profile2->generateUsername());
        $plaintextPassword = "intranet123";

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user2,
            $plaintextPassword
        );
        $user2->setPassword($hashedPassword);
        $user2->setProfile($profile2);
        $manager->persist($user2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }
}
