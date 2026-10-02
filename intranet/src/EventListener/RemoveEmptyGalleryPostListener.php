<?php

namespace App\EventListener;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * A gallery timeline post shows the photos of one upload. When the last of
 * them is deleted (from the gallery or EasyAdmin), delete the post too
 * instead of leaving an empty "added 0 photos" post.
 */
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Photo::class)]
class RemoveEmptyGalleryPostListener
{
    public function preRemove(Photo $photo, PreRemoveEventArgs $args): void
    {
        $post = $photo->getPost();
        if (!$post) {
            return;
        }

        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        if ($unitOfWork->isScheduledForDelete($post)) {
            return; // Already going away, e.g. the whole album is deleted
        }

        foreach ($post->getPhotos() as $other) {
            if ($other !== $photo && !$unitOfWork->isScheduledForDelete($other)) {
                return; // The post still has photos to show
            }
        }

        $args->getObjectManager()->remove($post);
    }
}
