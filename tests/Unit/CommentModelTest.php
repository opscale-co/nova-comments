<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Unit;

use Illuminate\Support\Facades\Auth;
use Opscale\NovaComments\Models\Comment;
use Opscale\NovaComments\Tests\TestCase;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CommentModelTest extends TestCase
{
    /**
     * @test
     */
    public function it_sets_commenter_id_from_authenticated_user_on_create(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Auth::login($user);

        $comment = Comment::create([
            'comment' => 'hello',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertSame($user->id, $comment->fresh()->commenter_id);
    }

    /**
     * @test
     */
    public function it_leaves_commenter_id_null_when_unauthenticated(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => 'system note',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertNull($comment->fresh()->commenter_id);
    }

    /**
     * @test
     */
    public function it_strips_tags_and_escapes_special_chars_by_default(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => '<script>alert("xss")</script>hi & bye',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $stored = $comment->fresh()->comment;

        $this->assertStringNotContainsString('<script>', $stored);
        $this->assertStringContainsString('hi', $stored);
        // FILTER_SANITIZE_SPECIAL_CHARS escapes & < > " '
        $this->assertStringContainsString('&#38;', $stored);
    }

    /**
     * @test
     */
    public function it_runs_the_when_creating_callback_instead_of_the_default_sanitizer(): void
    {
        Comment::whenCreating(function (Comment $c): void {
            $c->comment = strtoupper((string) $c->comment);
        });

        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => '<b>hello</b>',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        // Custom callback ran (uppercased) and the default sanitizer did NOT
        // (the <b> tag survives in upper-case form).
        $this->assertSame('<B>HELLO</B>', $comment->fresh()->comment);
    }

    /**
     * @test
     */
    public function when_creating_can_be_reset_to_null(): void
    {
        Comment::whenCreating(fn (Comment $c) => $c->comment = 'overridden');
        Comment::whenCreating(null);

        $post = Post::factory()->create();
        $comment = Comment::create([
            'comment' => 'normal',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertSame('normal', $comment->fresh()->comment);
    }

    /**
     * @test
     */
    public function it_resolves_commenter_relation_against_configured_user_model(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Auth::login($user);

        $comment = Comment::create([
            'comment' => 'hi',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->fresh()->commenter);
        $this->assertSame($user->id, $comment->commenter->id);
    }

    /**
     * @test
     */
    public function it_morphs_to_the_commentable(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => 'hi',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertInstanceOf(Post::class, $comment->fresh()->commentable);
        $this->assertSame($post->id, $comment->commentable->id);
    }
}
