<?php

namespace App\Tests\Controller;

use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Entity\Post;
use App\Tests\Traits\LogsInUserTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GalleryTest extends WebTestCase
{
    use LogsInUserTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn($this->client, $this->manager());
    }

    protected function tearDown(): void
    {
        $this->removeTestUserContent($this->manager());

        parent::tearDown();
    }

    public function testCreateAlbum(): void
    {
        $crawler = $this->client->request('GET', '/gallery/new');
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="photo_album"]')->form(), [
            'photo_album[title]' => 'Team lunch',
            'photo_album[eventDate]' => '2026-09-20',
        ], ['HTTP_ORIGIN' => 'http://localhost']);

        $album = $this->manager()->getRepository(PhotoAlbum::class)->findOneBy(['title' => 'Team lunch']);
        self::assertNotNull($album);
        self::assertResponseRedirects(sprintf('/gallery/%d', $album->getId()));
        self::assertSame('functional-test@example.com', $album->getCreatedBy()->getEmail());

        $this->client->request('GET', '/gallery/');
        self::assertSelectorTextContains('.album', 'Team lunch');
    }

    public function testAlbumWithoutTitleIsRejected(): void
    {
        $crawler = $this->client->request('GET', '/gallery/new');
        $this->client->submit($crawler->filter('form[name="photo_album"]')->form(), [
            'photo_album[title]' => '',
        ], ['HTTP_ORIGIN' => 'http://localhost']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.text-error', 'Give the album a title.');
    }

    public function testUploadPhotosToAlbum(): void
    {
        $album = $this->createAlbum();

        $this->upload($album, [$this->jpeg('one.jpg'), $this->jpeg('two.jpg')]);
        self::assertResponseRedirects(sprintf('/gallery/%d', $album->getId()));

        $photos = $this->manager()->getRepository(Photo::class)->findBy(['album' => $album]);
        self::assertCount(2, $photos);
        $dir = static::getContainer()->getParameter('kernel.project_dir').'/var/test/upload/gallery/';
        foreach ($photos as $photo) {
            self::assertFileExists($dir.$photo->getImageName());
            self::assertSame('functional-test@example.com', $photo->getUploadedBy()->getEmail());
        }

        $crawler = $this->client->request('GET', sprintf('/gallery/%d', $album->getId()));
        self::assertCount(2, $crawler->filter('[data-lightbox-target="item"]'));
        self::assertStringContainsString('gallery_large', $crawler->filter('[data-lightbox-target="item"]')->attr('data-src'));
    }

    public function testNonImageUploadIsRejected(): void
    {
        $album = $this->createAlbum();
        $text = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($text, 'not an image');

        $this->upload($album, [new UploadedFile($text, 'notes.txt', 'text/plain', null, true)]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.text-error', 'Only JPEG, PNG, WebP or GIF images can be uploaded.');
        self::assertCount(0, $this->manager()->getRepository(Photo::class)->findBy(['album' => $album]));
    }

    public function testOwnerDeletesPhotoAndAlbum(): void
    {
        $album = $this->createAlbum();
        $this->upload($album, [$this->jpeg('one.jpg')]);
        $photo = $this->manager()->getRepository(Photo::class)->findOneBy(['album' => $album]);
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $file = $projectDir.'/var/test/upload/gallery/'.$photo->getImageName();

        // Thumbnails LiipImagine would have generated for this photo
        $thumbnails = [];
        foreach (['gallery_thumb', 'gallery_large'] as $filter) {
            $thumbnail = sprintf('%s/public/media/cache/%s/upload/gallery/%s', $projectDir, $filter, $photo->getImageName());
            @mkdir(dirname($thumbnail), 0777, true);
            file_put_contents($thumbnail, 'thumbnail');
            $thumbnails[] = $thumbnail;
        }

        $crawler = $this->client->request('GET', sprintf('/gallery/%d', $album->getId()));
        $this->client->submit($crawler->filter(sprintf('form[action="/gallery/photo/%d/delete"]', $photo->getId()))->form());
        self::assertResponseRedirects(sprintf('/gallery/%d', $album->getId()));
        self::assertNull($this->manager()->getRepository(Photo::class)->find($photo->getId()));
        self::assertFileDoesNotExist($file, 'VichUploader removes the file with the photo');
        foreach ($thumbnails as $thumbnail) {
            self::assertFileDoesNotExist($thumbnail, 'Cached thumbnails are removed with the photo');
        }

        $crawler = $this->client->request('GET', sprintf('/gallery/%d', $album->getId()));
        $this->client->submit($crawler->filter(sprintf('form[action="/gallery/%d/delete"]', $album->getId()))->form());
        self::assertResponseRedirects('/gallery/');
        self::assertNull($this->manager()->getRepository(PhotoAlbum::class)->find($album->getId()));
    }

    public function testEachUploadIsPostedOnTheTimeline(): void
    {
        $album = $this->createAlbum();

        $this->upload($album, [$this->jpeg('one.jpg'), $this->jpeg('two.jpg')]);
        $this->upload($album, [$this->jpeg('three.jpg')]);

        $posts = $this->manager()->getRepository(Post::class)->findBy(['album' => $album], ['id' => 'ASC']);
        self::assertCount(2, $posts);
        self::assertCount(2, $posts[0]->getPhotos());
        self::assertCount(1, $posts[1]->getPhotos());
        self::assertSame('functional-test@example.com', $posts[0]->getUser()->getEmail());

        $crawler = $this->client->request('GET', '/');
        $albumPosts = $crawler->filter('.post-album')->reduce(fn ($node) => str_contains($node->text(), 'Test album'));
        self::assertCount(2, $albumPosts);
        $texts = $albumPosts->each(fn ($node) => $node->text());
        self::assertTrue((bool) array_filter($texts, fn ($t) => str_contains($t, 'added 2 photos to the album')));
        self::assertTrue((bool) array_filter($texts, fn ($t) => str_contains($t, 'added a photo to the album')));
    }

    public function testDeletingTheLastPhotoOfAnUploadRemovesItsPost(): void
    {
        $album = $this->createAlbum();
        $this->upload($album, [$this->jpeg('one.jpg'), $this->jpeg('two.jpg')]);
        [$first, $second] = $this->manager()->getRepository(Photo::class)->findBy(['album' => $album], ['id' => 'ASC']);
        $postId = $first->getPost()->getId();

        $this->deletePhoto($album, $first);
        self::assertNotNull($this->manager()->getRepository(Post::class)->find($postId), 'Post kept while it has photos');

        $this->deletePhoto($album, $second);
        self::assertNull($this->manager()->getRepository(Post::class)->find($postId), 'Post removed with its last photo');
    }

    public function testDeletingAnAlbumRemovesItsPostsWithLikesAndComments(): void
    {
        $album = $this->createAlbum();
        $this->upload($album, [$this->jpeg('one.jpg')]);
        $post = $this->manager()->getRepository(Post::class)->findOneBy(['album' => $album]);
        $postId = $post->getId();

        $this->client->request('GET', sprintf('/post/%d/like', $postId));
        $this->client->request('POST', sprintf('/post/%d/comment', $postId), [
            'comment' => ['message' => 'Great photos', '_token' => 'csrf-token'],
        ], [], ['HTTP_ORIGIN' => 'http://localhost']);
        $comment = $this->manager()->getRepository(\App\Entity\Comment::class)->findOneBy(['post' => $postId]);
        $this->client->request('GET', sprintf('/comment/%d/like', $comment->getId()));

        $crawler = $this->client->request('GET', sprintf('/gallery/%d', $album->getId()));
        $this->client->submit($crawler->filter(sprintf('form[action="/gallery/%d/delete"]', $album->getId()))->form());

        self::assertResponseRedirects('/gallery/');
        $this->manager()->clear();
        self::assertNull($this->manager()->getRepository(Post::class)->find($postId));
        self::assertSame(0, $this->manager()->getRepository(\App\Entity\Comment::class)->count(['post' => $postId]));
        self::assertSame(0, $this->manager()->getRepository(\App\Entity\Like::class)->count(['post' => $postId]));
    }

    private function deletePhoto(PhotoAlbum $album, Photo $photo): void
    {
        $crawler = $this->client->request('GET', sprintf('/gallery/%d', $album->getId()));
        $this->client->submit($crawler->filter(sprintf('form[action="/gallery/photo/%d/delete"]', $photo->getId()))->form());
        self::assertResponseRedirects(sprintf('/gallery/%d', $album->getId()));
        $this->manager()->clear();
    }

    private function createAlbum(): PhotoAlbum
    {
        $album = new PhotoAlbum();
        $album->setTitle('Test album');
        $album->setCreatedBy($this->manager()->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'functional-test@example.com']));
        $this->manager()->persist($album);
        $this->manager()->flush();

        return $album;
    }

    /** @param UploadedFile[] $files */
    private function upload(PhotoAlbum $album, array $files): void
    {
        $this->client->request(
            'POST',
            sprintf('/gallery/%d/photos', $album->getId()),
            ['photo_upload' => ['_token' => 'csrf-token']],
            ['photo_upload' => ['photos' => $files]],
            ['HTTP_ORIGIN' => 'http://localhost'],
        );
    }

    private function jpeg(string $name): UploadedFile
    {
        $image = imagecreatetruecolor(40, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 80, 40));
        $path = tempnam(sys_get_temp_dir(), 'jpg');
        imagejpeg($image, $path);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    private function manager(): \Doctrine\ORM\EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
