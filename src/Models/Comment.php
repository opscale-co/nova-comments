<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Opscale\NovaComments\Models\Repositories\CommentRepository;

class Comment extends Model
{
    use CommentRepository;

    protected $table = 'nova_comments';

    /** @var array<int, string> */
    protected $fillable = [
        'comment',
        'commentable_type',
        'commentable_id',
        'commenter_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Model, $this> */
    public function commenter(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel, 'commenter_id');
    }
}
