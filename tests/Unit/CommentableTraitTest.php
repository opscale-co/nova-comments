<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Opscale\NovaComments\Models\Comment;
use Opscale\NovaComments\Tests\TestCase;
use Workbench\App\Models\Post;

class CommentableTraitTest extends TestCase
{
    /**
     * @test
     */
    public function the_trait_exposes_a_morph_many_named_comments_pointing_at_the_comment_model(): void
    {
        $post = Post::factory()->create();

        $relation = $post->comments();

        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertSame(Comment::class, get_class($relation->getRelated()));
        $this->assertSame('commentable_type', $relation->getMorphType());
        $this->assertSame('nova_comments.commentable_id', $relation->getQualifiedForeignKeyName());
    }

    /**
     * @test
     */
    public function creating_comments_via_the_relation_stores_correct_morph_columns(): void
    {
        $post = Post::factory()->create();

        $comment = $post->comments()->create(['comment' => 'hello']);

        $row = $comment->fresh();
        $this->assertSame(Post::class, $row->commentable_type);
        $this->assertSame($post->id, $row->commentable_id);
    }
}
