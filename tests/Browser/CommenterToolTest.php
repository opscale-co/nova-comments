<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Browser;

use Laravel\Dusk\Browser;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CommenterToolTest extends BrowserTestCase
{
    /**
     * @test
     */
    public function it_renders_the_commenter_panel_on_the_post_detail_page(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $post): void {
            $browser->loginAs($user, 'web')
                ->visit("/nova/resources/posts/{$post->id}")
                ->waitFor('@commenter-textarea', 15)
                ->assertVisible('@commenter-save');
        });
    }

    /**
     * @test
     */
    public function it_creates_a_comment_when_the_save_button_is_clicked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $post): void {
            $browser->loginAs($user, 'web')
                ->visit("/nova/resources/posts/{$post->id}")
                ->waitFor('@commenter-textarea', 15)
                ->type('@commenter-textarea', 'hello from dusk')
                ->click('@commenter-save')
                ->waitFor('@commenter-list', 10)
                ->assertSeeIn('@commenter-list', 'hello from dusk');
        });

        $this->assertDatabaseHas('nova_comments', [
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'commenter_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_does_not_submit_an_empty_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $post): void {
            $browser->loginAs($user, 'web')
                ->visit("/nova/resources/posts/{$post->id}")
                ->waitFor('@commenter-textarea', 15)
                ->click('@commenter-save')
                ->pause(500)
                ->assertMissing('@commenter-list');
        });

        $this->assertDatabaseCount('nova_comments', 0);
    }
}
