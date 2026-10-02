<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use App\Entity\Event;
use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Entity\User;

/**
 * Timeline Service
 */
class TimelineService
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function addEvent($event){
        $post = new Post();
        $post->setEvent($event);
        $post->setUser($event->getUser());
        $this->em->persist($post);
        $this->em->flush();
    }

    /**
     * Announces photos just added to an album with a timeline post that
     * shows them. Persists the post; the caller flushes together with the
     * photos.
     *
     * @param Photo[] $photos
     */
    public function addPhotos(PhotoAlbum $album, array $photos, User $user): Post
    {
        $post = new Post();
        $post->setUser($user);
        $post->setAlbum($album);
        foreach ($photos as $photo) {
            $post->addPhoto($photo);
        }
        $this->em->persist($post);

        return $post;
    }

}
