<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Site;
use App\Entity\SiteActivity;
use App\Entity\User;
use App\Enum\ActivityStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteActivity>
 */
class SiteActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteActivity::class);
    }

    /**
     * Planned work of a user from $today on, soonest first; cancelled
     * activities are left out.
     *
     * @return SiteActivity[]
     */
    public function findUpcomingForUser(User $user, \DateTimeInterface $today): array
    {
        return $this->assignedTo($user)
            ->andWhere('a.date >= :today')
            ->andWhere('a.status != :cancelled')
            ->setParameter('today', $today, Types::DATE_MUTABLE)
            ->setParameter('cancelled', ActivityStatus::Cancelled)
            ->orderBy('a.date', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Work of a user whose date has passed but is still scheduled or in
     * progress, oldest first.
     *
     * @return SiteActivity[]
     */
    public function findOverdueForUser(User $user, \DateTimeInterface $today): array
    {
        return $this->assignedTo($user)
            ->andWhere('a.date < :today')
            ->andWhere('a.status IN (:pending)')
            ->setParameter('today', $today, Types::DATE_MUTABLE)
            ->setParameter('pending', [ActivityStatus::Scheduled, ActivityStatus::InProgress])
            ->orderBy('a.date', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Work of a user before $today, most recent first.
     *
     * @return SiteActivity[]
     */
    public function findPastForUser(User $user, \DateTimeInterface $today): array
    {
        return $this->assignedTo($user)
            ->andWhere('a.date < :today')
            ->setParameter('today', $today, Types::DATE_MUTABLE)
            ->orderBy('a.date', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Activities between two dates (inclusive) for the planning calendar,
     * optionally only of a company, a site or a user.
     *
     * @return SiteActivity[]
     */
    public function findForPlanning(\DateTimeInterface $from, \DateTimeInterface $to, ?Company $company = null, ?Site $site = null, ?User $user = null): array
    {
        $query = $this->createQueryBuilder('a')
            ->leftJoin('a.assignedUsers', 'u')
            ->leftJoin('a.site', 's')
            ->leftJoin('s.company', 'c')
            ->addSelect('u', 's', 'c')
            ->andWhere('a.date BETWEEN :from AND :to')
            ->setParameter('from', $from, Types::DATE_MUTABLE)
            ->setParameter('to', $to, Types::DATE_MUTABLE)
            ->orderBy('a.date', 'ASC')
            ->addOrderBy('c.legalName', 'ASC')
            ->addOrderBy('s.name', 'ASC');

        if ($company) {
            $query->andWhere('s.company = :company')->setParameter('company', $company);
        }
        if ($site) {
            $query->andWhere('a.site = :site')->setParameter('site', $site);
        }
        if ($user) {
            $query->andWhere(':user MEMBER OF a.assignedUsers')->setParameter('user', $user);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Activities assigned to $user, with site, company and all assignees
     * loaded for display.
     */
    private function assignedTo(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.assignedUsers', 'me', 'WITH', 'me = :user')
            ->leftJoin('a.assignedUsers', 'u')
            ->leftJoin('a.site', 's')
            ->leftJoin('s.company', 'c')
            ->addSelect('u', 's', 'c')
            ->setParameter('user', $user);
    }
}
