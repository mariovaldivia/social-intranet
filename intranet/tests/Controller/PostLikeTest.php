<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\Post;
use App\Tests\Traits\LogsInUserTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostLikeTest extends WebTestCase
{
    use LogsInUserTrait;

    protected function tearDown(): void
    {
        $this->removeTestUserContent(static::getContainer()->get('doctrine')->getManager());

        parent::tearDown();
    }

    public function testLikeTogglesAndReturnsActionsFragment(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $user = $this->logIn($client, $manager);

        $post = new Post();
        $post->setUser($user);
        $post->setMessage('Post to like');
        $manager->persist($post);
        $manager->flush();

        // app.js swaps the .post-actions element with this response
        $crawler = $client->request('GET', sprintf('/post/%d/like', $post->getId()));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('div.post-actions'));
        self::assertStringContainsString('btn-soft', $crawler->filter('a.post-like')->attr('class'));
        self::assertSame('1', trim($crawler->filter('a[data-info-modal]')->text()));

        $crawler = $client->request('GET', sprintf('/post/%d/like', $post->getId()));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('btn-ghost', $crawler->filter('a.post-like')->attr('class'));
        self::assertCount(0, $crawler->filter('a[data-info-modal]'));
    }

    public function testEventListUsesAjaxLikeButton(): void
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $user = $this->logIn($client, $manager);

        $event = new Event();
        $event->setUser($user);
        $event->setDescription('Event with a liked post');
        $event->setDate(new \DateTime('+1 day'));
        $manager->persist($event);

        $post = new Post();
        $post->setUser($user);
        $post->setEvent($event);
        $manager->persist($post);
        $manager->flush();

        $client->request('GET', sprintf('/post/%d/like', $post->getId()));

        // Used to crash when the event post had likes (missing id in _likes)
        $crawler = $client->request('GET', '/event/');
        self::assertResponseIsSuccessful();
        $likeButton = $crawler->filter(sprintf('a.post-like[href="/post/%d/like"]', $post->getId()));
        self::assertCount(1, $likeButton);
        self::assertCount(1, $likeButton->closest('.post-actions'));
    }
}
