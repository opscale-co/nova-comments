<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Resource;
use Opscale\NovaComments\Models\Comment as CommentModel;

class Comment extends Resource
{
    /** @var class-string<CommentModel> */
    public static $model = CommentModel::class;

    public static $title = 'id';

    /** @var array<int, string> */
    public static $search = ['comment'];

    public static function label(): string
    {
        return __('nova-comments::resource.label');
    }

    public static function singularLabel(): string
    {
        return __('nova-comments::resource.singular');
    }

    public static function availableForNavigation(Request $request): bool
    {
        return false;
    }

    /** @return class-string */
    private static function commenterResource(): string
    {
        $resource = config('nova-comments.commenter.nova-resource')
            ?: Nova::resourceForModel((string) config('auth.providers.users.model'));

        return (string) $resource;
    }

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Trix::make(__('nova-comments::fields.comment'), 'comment')
                ->alwaysShow()
                ->hideFromIndex()
                ->rules('required'),

            MorphTo::make(__('nova-comments::fields.commentable'), 'commentable')->onlyOnIndex(),

            Text::make(__('nova-comments::fields.comment'), 'comment')
                ->displayUsing(fn (?string $value): string => Str::limit(
                    trim(strip_tags((string) $value)),
                    (int) config('nova-comments.limit', 100),
                ))
                ->onlyOnIndex(),

            BelongsTo::make(
                __('nova-comments::fields.commenter'),
                'commenter',
                self::commenterResource(),
            )->exceptOnForms(),

            DateTime::make(__('nova-comments::fields.created'), 'created_at')
                ->exceptOnForms()
                ->sortable(),
        ];
    }

    /** @return array<int, mixed> */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
