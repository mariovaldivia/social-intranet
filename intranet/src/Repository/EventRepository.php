<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Events happening on $today or later, soonest first.
     *
     * @return Event[]
     */
    public function findUpcoming(\DateTimeInterface $today, ?int $limit = null): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->setParameter('today', $today, Types::DATE_MUTABLE)
            ->orderBy('e.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Events that happened before $today, most recent first.
     *
     * @return Event[]
     */
    public function findPast(\DateTimeInterface $today): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date < :today')
            ->setParameter('today', $today, Types::DATE_MUTABLE)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
