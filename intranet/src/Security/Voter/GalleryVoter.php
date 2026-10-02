<?php

namespace App\Security\Voter;

use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may delete gallery content: admins, the album's creator (album and
 * any photo in it) and the person who uploaded a photo.
 *
 * @extends Voter<string, PhotoAlbum|Photo>
 */
class GalleryVoter extends Voter
{
    public const DELETE = 'GALLERY_DELETE';

    public function __construct(private AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::DELETE === $attribute && ($subject instanceof PhotoAlbum || $subject instanceof Photo);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($subject instanceof Photo) {
            return $subject->getUploadedBy() === $user || $subject->getAlbum()?->getCreatedBy() === $user;
        }

        return $subject->getCreatedBy() === $user;
    }
}
