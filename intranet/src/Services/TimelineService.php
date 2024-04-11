<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use App\Entity\Event;

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

}
