<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Unit;

use Illuminate\Support\Facades\Auth;
use Opscale\NovaComments\Models\Comment;
use Opscale\NovaComments\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CommentModelTest extends TestCase
{
    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_sanitizes_dangerous_html_by_default(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => '<script>alert("xss")</script>hi & bye'
                .'<a href="javascript:alert(1)">link</a>'
                .'<img src="x" onerror="alert(1)">',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $stored = $comment->fresh()->comment;

        // Script element and its content are gone.
        $this->assertStringNotContainsString('<script>', $stored);
        $this->assertStringNotContainsString('alert', $stored);
        // The javascript: URL and the inline event handler are stripped.
        $this->assertStringNotContainsString('javascript:', $stored);
        $this->assertStringNotContainsString('onerror', $stored);
        // Plain text survives.
        $this->assertStringContainsString('hi', $stored);
        $this->assertStringContainsString('bye', $stored);
    }

    #[Test]
    public function it_keeps_rich_text_and_attachment_markup_when_sanitizing(): void
    {
        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => '<div><strong>bold</strong> and a '
                .'<a href="/storage/nova-comments/attachments/a.png">file</a></div>'
                .'<figure class="attachment" data-trix-attachment="{}">'
                .'<img src="/storage/nova-comments/attachments/a.png" alt="a"></figure>',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $stored = $comment->fresh()->comment;

        $this->assertStringContainsString('<strong>bold</strong>', $stored);
        $this->assertStringContainsString('<figure', $stored);
        // The uploaded attachment URL is preserved so it renders after saving.
        $this->assertStringContainsString('/storage/nova-comments/attachments/a.png', $stored);
    }

    #[Test]
    public function it_does_not_sanitize_when_the_sanitizer_is_disabled(): void
    {
        config()->set('nova-comments.sanitizer.enabled', false);

        $post = Post::factory()->create();

        $comment = Comment::create([
            'comment' => '<script>alert("xss")</script>keep',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertStringContainsString('<script>', $comment->fresh()->comment);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
