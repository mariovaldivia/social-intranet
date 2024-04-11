<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Management;
use App\Entity\Department;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $operaciones = new Management();
        $operaciones->setName('Gerencia de Operaciones');
        $manager->persist($operaciones);

        $produccion = new Management();
        $produccion->setName('Gerencia de Produccion');
        $manager->persist($produccion);


        $ti = new Deparment();
        $ti->setName('TI');
        $ti->setManagement($operaciones);
        $manager->persist($ti);

        $prod = new Deparment();
        $prod->setName('Producción');
        $prod->setManagement($produccion);
        $manager->persist($prod);
        $manager->flush();
    }
}
