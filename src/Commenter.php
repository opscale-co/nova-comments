<?php

declare(strict_types=1);

namespace Opscale\NovaComments;

use Laravel\Nova\ResourceTool;

class Commenter extends ResourceTool
{
    public function name(): string
    {
        return (string) config('nova-comments.comments-panel.name')
            ?: (string) __('nova-comments::panel.name');
    }

    public function component(): string
    {
        return 'commenter';
    }
}
