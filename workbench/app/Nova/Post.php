<?php

declare(strict_types=1);

namespace Workbench\App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Opscale\NovaComments\Commenter;
use Workbench\App\Models\Post as PostModel;

class Post extends Resource
{
    public static string $model = PostModel::class;

    public static $title = 'title';

    public static $search = ['id', 'title'];

    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Title')->sortable()->rules('required', 'max:255'),
            Textarea::make('Body')->alwaysShow(),
            Commenter::make(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
