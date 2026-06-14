<?php

declare(strict_types=1);

namespace Opscale\NovaComments;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Opscale\NovaComments\Models\Comment;

trait Commentable
{
    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
