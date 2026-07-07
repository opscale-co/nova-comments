<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Feature;

use Illuminate\Http\Request;
use Opscale\NovaComments\Commenter;
use Opscale\NovaComments\Nova\Comment as NovaComment;
use Opscale\NovaComments\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function comment_resource_is_never_navigable(): void
    {
        $this->assertFalse(NovaComment::availableForNavigation(new Request));
    }

    /**
     * @test
     */
    public function comments_panel_name_drives_resource_tool_name(): void
    {
        config(['nova-comments.comments-panel.name' => 'Notes']);

        $tool = Commenter::make();

        $this->assertSame('Notes', $tool->name());
        $this->assertSame('commenter', $tool->component());
    }

    /**
     * @test
     */
    public function default_config_values_loaded(): void
    {
        $this->assertSame(100, config('nova-comments.limit'));
        $this->assertNull(config('nova-comments.comments-panel.name'));
        $this->assertNull(config('nova-comments.commenter.nova-resource'));
    }

    /**
     * @test
     */
    public function panel_name_falls_back_to_translation_when_config_is_null(): void
    {
        config(['nova-comments.comments-panel.name' => null]);

        $tool = Commenter::make();

        $this->assertSame(__('nova-comments::panel.name'), $tool->name());
    }
}
