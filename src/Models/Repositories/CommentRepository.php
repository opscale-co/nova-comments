<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Models\Repositories;

use Closure;
use Illuminate\Support\Facades\Auth;
use Opscale\NovaComments\Models\Comment;

trait CommentRepository
{
    protected static ?Closure $whenCreating = null;

    public static function whenCreating(?callable $callback): void
    {
        static::$whenCreating = $callback === null ? null : Closure::fromCallable($callback);
    }

    protected static function bootCommentRepository(): void
    {
        static::creating(function (Comment $comment): void {
            if (Auth::check()) {
                $comment->commenter_id = Auth::id();
            }

            if (static::$whenCreating !== null) {
                (static::$whenCreating)($comment);
            }
        });
    }
}
