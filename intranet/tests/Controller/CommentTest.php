<?php

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Tests\Traits\LogsInUserTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentTest extends WebTestCase
{
    use LogsInUserTrait;

    protected function tearDown(): void
    {
        $this->removeTestUserContent(static::getContainer()->get('doctrine')->getManager());

        parent::tearDown();
    }

    public function testValidCommentReturnsCommentFragment(): void
    {
        [$client, $post] = $this->createPost();

        // The comments controller appends this to its list; the root keeps the
        // replace controller so its Like button works once inserted
        $crawler = $client->request('POST', sprintf('/post/%d/comment', $post->getId()), [
            'comment' => ['message' => 'Nice post', '_token' => 'csrf-token'],
        ], [], ['HTTP_ORIGIN' => 'http://localhost']);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('body > div.comment[data-controller="replace"]'));
        self::assertStringContainsString('Nice post', $crawler->filter('div.comment p')->text());
    }

    public function testBlankCommentReturnsFormWithError(): void
    {
        [$client, $post] = $this->createPost();

        $crawler = $client->request('POST', sprintf('/post/%d/comment', $post->getId()), [
            'comment' => ['message' => '   ', '_token' => 'csrf-token'],
        ], [], ['HTTP_ORIGIN' => 'http://localhost']);

        // The comments controller shows a 422 response in place of the form
        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter('form[name="comment"][data-action="comments#submit"]'));
        self::assertSelectorTextContains('.text-error', 'Write something before commenting.');
    }

    public function testCommentsAreListedOldestFirst(): void
    {
        [$client, $post] = $this->createPost();

        foreach (['First', 'Second'] as $message) {
            $client->request('POST', sprintf('/post/%d/comment', $post->getId()), [
                'comment' => ['message' => $message, '_token' => 'csrf-token'],
            ], [], ['HTTP_ORIGIN' => 'http://localhost']);
        }

        $crawler = $client->request('GET', '/');
        $messages = $crawler->filter('.comments .comment p')->each(fn ($node) => $node->text());
        self::assertSame(['First', 'Second'], array_values(array_intersect($messages, ['First', 'Second'])));
    }

    /** @return array{0: \Symfony\Bundle\FrameworkBundle\KernelBrowser, 1: Post} */
    private function createPost(): array
    {
        $client = static::createClient();
        $manager = static::getContainer()->get('doctrine')->getManager();
        $user = $this->logIn($client, $manager);

        $post = new Post();
        $post->setUser($user);
        $post->setMessage('Post to comment');
        $manager->persist($post);
        $manager->flush();

        return [$client, $post];
    }
}
