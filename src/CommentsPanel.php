<?php

declare(strict_types=1);

namespace Opscale\NovaComments;

use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Panel;
use Opscale\NovaComments\Nova\Comment;

class CommentsPanel extends Panel
{
    public function __construct()
    {
        parent::__construct($this->panelName(), $this->prepareFields($this->fields()));
    }

    /** @return array<int, mixed> */
    protected function fields(): array
    {
        return [
            MorphMany::make(
                $this->panelName(),
                'comments',
                Comment::class,
            ),
        ];
    }

    protected function panelName(): string
    {
        return (string) config('nova-comments.comments-panel.name')
            ?: (string) __('nova-comments::panel.name');
    }
}
