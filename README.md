## Support us

At Opscale, we’re passionate about contributing to the open-source community by providing solutions that help businesses scale efficiently. If you’ve found our tools helpful, here are a few ways you can show your support:

⭐ **Star this repository** to help others discover our work and be part of our growing community. Every star makes a difference!

💬 **Share your experience** by leaving a review on [Trustpilot](https://www.trustpilot.com/review/opscale.co) or sharing your thoughts on social media.

📧 **Send us feedback** on what we can improve at [feedback@opscale.co](mailto:feedback@opscale.co).

🙏 **Get involved** by actively contributing to our open-source repositories.

💼 **Hire us** at hire@opscale.co for custom dashboards, admin panels, internal tools, or MVPs.

Thanks for helping Opscale continue to scale! 🚀

---

## opscale-co/nova-comments

A Laravel Nova 5 field that adds polymorphic comments to any Nova Resource. This is the Opscale-maintained fork of [`kirschbaum-development/nova-comments`](https://github.com/kirschbaum-development/nova-comments) — same surface area (Commenter ResourceTool, Commentable trait, CommentsPanel, `nova_comments` table, `whenCreating` sanitization hook) under Opscale conventions (`declare(strict_types=1)`, PHPStan level 8, Duster, Semantic Release, single-tenant deployment model).

## Installation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/opscale-co/nova-comments.svg?style=flat-square)](https://packagist.org/packages/opscale-co/nova-comments)

```bash
composer require opscale-co/nova-comments
php artisan migrate
```

The service provider auto-registers via package discovery. Publish the config if you need to customize it:

```bash
php artisan vendor:publish --tag=nova-comments-config
```

## Usage

### 1. Add the `Commentable` trait to a model

```php
use Opscale\NovaComments\Commentable;

class Post extends Model
{
    use Commentable;
}
```

### 2. Add the `Commenter` field to the model's Nova Resource

```php
use Opscale\NovaComments\Commenter;

public function fields(NovaRequest $request): array
{
    return [
        // ... your other fields ...
        Commenter::make(),
    ];
}
```

That's it. The comments panel renders on the resource detail page with a Trix rich-text editor (bold, italic, lists, links, etc.). Authenticated Nova users can write comments, paginate older / newer, and submit with the Save button or ⌘+Enter.

### Alternative: the relationship panel

Prefer Nova's standard relationship table over the inline panel? Use the `CommentsPanel`:

```php
use Opscale\NovaComments\CommentsPanel;

public function fields(NovaRequest $request): array
{
    return [
        // ... fields ...
        new CommentsPanel(),
    ];
}
```

### Custom sanitization

By default, comments are stored as the Trix-produced HTML as-is. Trix sanitizes input client-side (no `<script>`, no inline event handlers), which covers the common case. If you need server-side sanitization — Markdown rendering, an HTML purifier, or just to be paranoid about direct API calls — register a callback in a service provider:

```php
use Opscale\NovaComments\Models\Comment;

public function boot(): void
{
    Comment::whenCreating(function (Comment $comment): void {
        $comment->comment = MyPurifier::clean($comment->comment);
    });
}
```

Pass `null` to clear the callback.

## Configuration

`config/nova-comments.php`:

| Key | Default | Purpose |
|---|---|---|
| `commenter.nova-resource` | `\App\Nova\User::class` | Nova resource used for the `commenter` BelongsTo field |
| `comments-panel.name` | `null` (uses translation) | Heading shown above the panel |
| `limit` | `100` | Max length of the comment preview on the index page |

## Testing

```bash
composer install
vendor/bin/phpunit --exclude-testsuite=Browser
```

Browser (Dusk) tests run against a workbench Nova app:

```bash
npm ci && npm run prod      # build dist/ assets
vendor/bin/phpunit --testsuite=Browser
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

Please see [CONTRIBUTING](https://github.com/opscale-co/.github/blob/main/CONTRIBUTING.md).

## Credits

- [Opscale](https://github.com/opscale-co)
- Inspired by [`kirschbaum-development/nova-comments`](https://github.com/kirschbaum-development/nova-comments) — © Kirschbaum Development Group LLC

## License

The MIT License (MIT). Please see [License File](LICENSE.md).
