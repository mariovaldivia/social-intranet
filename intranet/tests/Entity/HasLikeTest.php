<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * hasLike() must find the user's like wherever it sits in the collection.
 * An initialized collection keeps its keys when filtered, so the user's like
 * is not at index 0 when someone else liked first.
 */
class HasLikeTest extends TestCase
{
    public function testPostHasLikeWhenAnotherUserLikedFirst(): void
    {
        [$me, $other] = [new User(), new User()];
        $post = new Post();

        self::assertFalse($post->hasLike($me));

        $post->addLike((new Like())->setUser($other));
        self::assertFalse($post->hasLike($me));

        $post->addLike((new Like())->setUser($me));
        self::assertTrue($post->hasLike($me));
    }

    public function testCommentHasLikeWhenAnotherUserLikedFirst(): void
    {
        [$me, $other] = [new User(), new User()];
        $comment = new Comment();

        self::assertFalse($comment->hasLike($me));

        $comment->addLike((new Like())->setUser($other));
        self::assertFalse($comment->hasLike($me));

        $comment->addLike((new Like())->setUser($me));
        self::assertTrue($comment->hasLike($me));
    }
}
