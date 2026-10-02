<?php

namespace App\Tests\Security;

use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Entity\User;
use App\Security\Voter\GalleryVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GalleryVoterTest extends TestCase
{
    private User $owner;
    private User $uploader;
    private User $other;
    private PhotoAlbum $album;
    private Photo $photo;

    protected function setUp(): void
    {
        [$this->owner, $this->uploader, $this->other] = [new User(), new User(), new User()];
        $this->album = (new PhotoAlbum())->setCreatedBy($this->owner);
        $this->photo = (new Photo())->setUploadedBy($this->uploader);
        $this->album->addPhoto($this->photo);
    }

    public function testAlbumCreatorCanDeleteAlbumAndItsPhotos(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->owner, $this->album));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->owner, $this->photo));
    }

    public function testUploaderCanDeleteOwnPhotoButNotTheAlbum(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->uploader, $this->photo));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->uploader, $this->album));
    }

    public function testOtherUsersCannotDelete(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->other, $this->album));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->other, $this->photo));
    }

    public function testAdminsCanDeleteAnything(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->other, $this->album, isAdmin: true));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->other, $this->photo, isAdmin: true));
    }

    private function vote(User $user, object $subject, bool $isAdmin = false): int
    {
        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager->method('decide')->willReturn($isAdmin);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        return (new GalleryVoter($decisionManager))->vote($token, $subject, [GalleryVoter::DELETE]);
    }
}
