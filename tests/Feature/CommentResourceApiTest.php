<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Feature;

use Opscale\NovaComments\Models\Comment;
use Opscale\NovaComments\Tests\TestCase;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CommentResourceApiTest extends TestCase
{
    /**
     * @test
     */
    public function posting_to_nova_api_comments_creates_a_comment_attributed_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/nova-api/comments', [
                'comment' => 'hello from nova',
                'viaResource' => 'posts',
                'viaResourceId' => $post->id,
                'viaRelationship' => 'comments',
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('nova_comments', 1);
        $stored = Comment::firstOrFail();

        $this->assertSame($user->id, $stored->commenter_id);
        $this->assertSame(Post::class, $stored->commentable_type);
        $this->assertSame($post->id, $stored->commentable_id);
        $this->assertStringContainsString('hello from nova', $stored->comment);
    }

    /**
     * @test
     */
    public function getting_nova_api_comments_returns_comments_in_desc_created_at_order(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user);

        $first = Comment::create([
            'comment' => 'older',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
        $first->forceFill([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ])->saveQuietly();

        $second = Comment::create([
            'comment' => 'newer',
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $response = $this->getJson(sprintf(
            '/nova-api/comments?orderBy=created_at&orderByDirection=desc&viaResource=posts&viaResourceId=%d&viaRelationship=comments&relationshipType=hasMany',
            $post->id,
        ));

        $response->assertSuccessful();
        $resources = $response->json('resources');
        $this->assertNotEmpty($resources);
        // First in payload should be the newest.
        $this->assertSame($second->id, $resources[0]['id']['value'] ?? null);
    }

    /**
     * @test
     */
    public function unauthenticated_post_is_rejected_by_nova(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/nova-api/comments', [
            'comment' => 'no auth',
            'viaResource' => 'posts',
            'viaResourceId' => $post->id,
            'viaRelationship' => 'comments',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('nova_comments', 0);
    }
}
