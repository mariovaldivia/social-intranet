<?php

namespace App\Repository;

use App\Entity\PhotoAlbum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoAlbum>
 */
class PhotoAlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoAlbum::class);
    }

    /**
     * Albums for the gallery page, most recent activity first (albums without
     * an event date go last), with their photos loaded for cover and count.
     *
     * @return PhotoAlbum[]
     */
    public function findForGallery(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.photos', 'p')
            ->addSelect('p')
            ->orderBy('a.eventDate', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
