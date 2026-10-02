<?php

namespace App\EventListener;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

/**
 * When VichUploader deletes an uploaded image (entity removed or file
 * replaced), also delete its LiipImagine thumbnails. Otherwise they stay in
 * public/media/cache and a deleted photo remains reachable by its URL.
 */
#[AsEventListener(event: Events::PRE_REMOVE)]
class RemoveImageThumbnailsListener
{
    // Liip filter sets rendered for each Vich mapping
    private const FILTERS = [
        'gallery' => ['gallery_thumb', 'gallery_large'],
        'users' => ['user', 'user_post', 'user_comment'],
    ];

    public function __construct(private CacheManager $cacheManager)
    {
    }

    public function __invoke(Event $event): void
    {
        $mapping = $event->getMapping();
        $filters = self::FILTERS[$mapping->getMappingName()] ?? null;
        $fileName = $mapping->getFileName($event->getObject());

        if ($filters && $fileName) {
            // Same path the templates pass to imagine_filter (vich_uploader_asset)
            $this->cacheManager->remove($mapping->getUriPrefix().'/'.$fileName, $filters);
        }
    }
}
