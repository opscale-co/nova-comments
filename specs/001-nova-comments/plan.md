# Implementation Plan: nova-comments package

**Branch**: `001-nova-comments` | **Date**: 2026-06-04 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-nova-comments/spec.md`

## Summary

Ship a Laravel Nova 5 package that adds polymorphic comments to any Nova Resource. Replicate `kirschbaum-development/nova-comments`'s surface (Commenter ResourceTool, Commentable trait, CommentsPanel, polymorphic `nova_comments` table, sanitization hook, `whenCreating()` override) under Opscale conventions: strict types, PHPStan-clean, declarative model, Repository-free (Nova field whitelist is the boundary), and validated via Unit + Feature + Browser tests inside an `orchestra/testbench-dusk` workbench app.

## Technical Context

**Language/Version**: PHP 8.2+
**Primary Dependencies**: `laravel/nova ^5.4`
**Dev Dependencies**: `orchestra/testbench-dusk ^9.0`, `phpunit/phpunit ^10.5`, `tightenco/duster ^2.7`, `larastan/larastan ^2.10`
**Storage**: Single relational table `nova_comments` (host DB; supports MySQL / Postgres / SQLite — workbench uses SQLite)
**Testing**: PHPUnit (Unit + Feature), Laravel Dusk via testbench-dusk (Browser)
**Target Platform**: Laravel ^11 + Nova ^5.4 on PHP 8.2+
**Project Type**: package
**Performance Goals**: comment write + read round-trip under 200 ms on the workbench SQLite store; not a primary optimization target
**Constraints**: no custom HTTP routes (reuse Nova's resource API); no new server-side dependencies beyond Nova; built JS/CSS committed under `dist/`
**Scale/Scope**: ~12 PHP source files, ~3 Vue components, 1 migration, 1 config file, ~6 test classes

## Constitution Check

| Article | Compliance |
|---|---|
| 0. Project Type | `package` — full code, no spec-flow plan steps (per user feedback [[feedback-packages-replicate-existing]]) |
| I. Priority order | Business correctness (polymorphic comments parity with upstream) wins. UI is a near-pixel copy of upstream's Vue, kept simple. |
| III. Clean Architecture | `Models/Comment` = Representation; `Nova/Comment` + `Commenter` + `CommentsPanel` = Interaction; the model's `creating` hook is the only Transformation, kept minimal. No Actions because no business workflow exists — this is a CRUD-on-polymorphic-relation library, not a process. **Deviation**: the `creating` hook contains a small conditional (sanitizer vs. callback). Documented inline. |
| IV. DDD — ULID PKs | **Deviation**: PK on `nova_comments` is `bigIncrements` to match upstream's `commentable_id` / `commenter_id` columns. ULID would force every host to migrate to ULID-keyed users — out of scope for v1. Documented in spec Assumptions and migration comment. |
| V. Opscale Actions | Not applicable — no Action class because no BPMN. The package is library code, not a process. |
| VI. Nova Layer | `Nova/Comment` resource has no business logic; field display logic only. |
| VII. Outputs | Not applicable — no notifications, PDFs, webhooks. |
| VIII. SOLID + strict_types | All `src/` files declare strict types; PHPStan level 8 in CI. |
| IX. Single-tenant | No `tenant_id`. ✅ |
| X. Quality gates | PHPStan 8, Duster, all tests, SonarQube wired in `opscale-release` step. |

Gate result: ✅ Two documented deviations (ULID PK, single conditional in model hook); proceed.

## Project Structure

### Documentation (this feature)

```text
specs/001-nova-comments/
├── spec.md
├── plan.md          # this file
└── tasks.md         # produced by /speckit.tasks
```

### Source Code (repository root)

```text
src/
├── ToolServiceProvider.php          # registers config, migration, Nova resource, JS/CSS assets
├── Commentable.php                   # trait — morphMany comments()
├── Commenter.php                     # ResourceTool — entry point in Resource::fields()
├── CommentsPanel.php                 # Panel — convenience wrapper around MorphMany field
├── Models/
│   └── Comment.php                   # Eloquent model — table nova_comments, creating hook
└── Nova/
    └── Comment.php                   # Nova Resource — wires /nova-api/comments

config/
└── nova-comments.php                 # published config

database/
└── migrations/
    └── 2026_06_04_000000_create_nova_comments_table.php

resources/
├── css/
│   └── tool.css                       # empty (Tailwind via Nova)
└── js/
    ├── tool.js                        # Nova.booting → registers 'commenter' component
    └── components/
        ├── Tool.vue                   # the panel
        └── Comment.vue                # single-comment rendering

dist/                                  # built artifacts (committed)
├── css/tool.css
└── js/tool.js

workbench/                             # testbench workbench app (gitignored .data/)
├── app/
│   ├── Models/Post.php
│   ├── Nova/Post.php
│   └── Providers/
│       ├── WorkbenchServiceProvider.php
│       └── NovaServiceProvider.php
├── database/
│   ├── factories/UserFactory.php
│   ├── factories/PostFactory.php
│   └── seeders/DatabaseSeeder.php
└── routes/

tests/
├── TestCase.php                                # base class wiring testbench + this provider
├── Unit/
│   ├── CommentModelTest.php                    # creating hook + whenCreating override
│   └── CommentableTraitTest.php
├── Feature/
│   ├── CommentResourceApiTest.php              # /nova-api/comments POST + GET via Nova
│   └── ConfigurationTest.php                   # config keys wire through
└── Browser/
    ├── DuskTestCase.php
    └── CommenterToolTest.php                   # type → save → see comment

composer.json
phpunit.xml.dist
testbench.yaml                                  # already created by opscale-init
nova.mix.js
webpack.mix.js
package.json
```

## File-by-file design decisions

### `src/ToolServiceProvider.php`
Renamed conceptually (kept the file `ToolServiceProvider.php` so the `composer.json` `extra.laravel.providers` entry already auto-discovers). Boots:
- merge + publish `config/nova-comments.php`
- `loadMigrationsFrom('database/migrations')`
- `Nova::resources([Nova\Comment::class])`
- `Nova::serving(...)` → `Nova::script('commentable', __DIR__.'/../dist/js/tool.js')` and matching `Nova::style`.

### `src/Commenter.php`
`ResourceTool` subclass. `name()` returns `config('nova-comments.comments-panel.name', 'Comments')` so renaming via config also renames the heading. `component()` returns `'commenter'` — must match `Nova.booting` registration in JS.

### `src/Commentable.php`
Trait. Single method `comments(): MorphMany`. PHPDoc generic `MorphMany<Comment>`.

### `src/CommentsPanel.php`
Optional convenience. Wraps `MorphMany::make($name, 'comments', Nova\Comment::class)` inside a `Panel`. Hosts that want the standard Nova relationship panel instead of the rich Vue tool can use this.

### `src/Models/Comment.php`
- `protected $table = 'nova_comments';`
- `protected $fillable = ['comment', 'commentable_type', 'commentable_id', 'commenter_id'];` ← deviation from upstream which had no fillable. Nova's field whitelist makes upstream's omission safe, but declaring `$fillable` is required for our Unit tests to do `Comment::create()`.
- `protected static ?\Closure $whenCreating = null;`
- `boot()` registers the `creating` listener exactly per upstream behavior: set `commenter_id` from `auth()->id()` if checked, then EITHER run the registered callback OR run the default sanitizer. Conditional documented in a single-line comment.
- `commentable(): MorphTo`, `commenter(): BelongsTo`.
- `whenCreating(callable $cb): void` — static registrar; tests reset via `Comment::whenCreating(null)` (we extend the registrar to accept null).

### `src/Nova/Comment.php`
Identical surface to upstream:
- `Textarea::make('comment')->alwaysShow()->hideFromIndex()`
- `MorphTo::make('Commentable')->onlyOnIndex()`
- `Text::make('comment')` truncated by `config('nova-comments.limit')` — onlyOnIndex
- `BelongsTo::make('Commenter', 'commenter', config('nova-comments.commenter.nova-resource'))->exceptOnForms()`
- `DateTime::make('Created', 'created_at')->exceptOnForms()->sortable()`
- `availableForNavigation()` from config
- `static $model = Comment::class`, `static $title = 'id'`, `static $search = ['comment']`

### `database/migrations/2026_06_04_000000_create_nova_comments_table.php`
`bigIncrements('id')` (modern Laravel default, slight upgrade from upstream's `increments`), `morphs('commentable')`, `unsignedBigInteger('commenter_id')->nullable()` (upgraded from `unsignedInteger` to match `User.id`), `text('comment')`, `timestamps()`.

### `config/nova-comments.php`
1:1 copy of upstream keys: `available-for-navigation`, `commenter.nova-resource`, `comments-panel.name`, `limit`.

### Vue components
- `tool.js`: `Nova.booting((app) => app.component('commenter', Tool))`.
- `Tool.vue`: textarea + button + paginated list. Adds `dusk="commenter-textarea"`, `dusk="commenter-save"`, `dusk="commenter-list"` selectors so the Browser test can target elements deterministically.
- `Comment.vue`: renders one comment. Reads commenter URL from `Nova.config('base')` + commenter URI key derived from the BelongsTo field. We keep upstream's `/resources/users/{id}` hardcode for v1 to match parity (limitation called out in spec).

### `dist/` build artifacts
The Vue source compiles via `webpack.mix.js` to `dist/js/tool.js` + `dist/css/tool.css`. For tests we need these files to exist — the Browser test pipeline must run `npm ci && npm run prod` once before `vendor/bin/phpunit --testsuite=Browser`. Document this in the test task.

### Workbench
testbench-dusk needs a Laravel app to boot Nova against. We seed:
- One User
- One Post model with `Commentable` trait
- One `Nova/Post` resource declaring `Commenter::make()` in fields
- Login wired through Dusk's `/_dusk/login` (provided by `Laravel\Dusk\DuskServiceProvider`, listed in `testbench.yaml`).

## Test strategy

| Layer | What it proves | Tools |
|---|---|---|
| Unit — `CommentModelTest` | `creating` sets `commenter_id` from auth; sanitizer escapes `<script>` tags; `whenCreating` callback replaces the sanitizer; resetting to null restores defaults | PHPUnit, `Auth::login()` on a factory user |
| Unit — `CommentableTraitTest` | `comments()` returns a `MorphMany` pointing at `Comment::class` with correct morph type / id | PHPUnit |
| Feature — `CommentResourceApiTest` | POST `/nova-api/comments` with `viaResource=posts&viaResourceId=1&comment=...` creates a comment attributed to the authed user and linked to the right Post; GET lists in DESC order with the correct relationship filters | testbench HTTP kernel, `actingAs()`, `loginAs()` for Nova |
| Feature — `ConfigurationTest` | `nova-comments.available-for-navigation=false` removes the resource from navigation; `nova-comments.limit` truncates index display | testbench |
| Browser — `CommenterToolTest` | type "hello dusk" into textarea, click Save, see "hello dusk" appear in the rendered list with the right author; ⌘+Enter also works | testbench-dusk + headless Chrome |

## Out of scope (v1)

- ULID-keyed host support
- Per-record authorization policy
- Markdown / WYSIWYG comments (only `whenCreating` callback hook is provided)
- Edit / delete comments from the panel UI (upstream parity)
- Reply threads / nested comments
- Notifications on new comments

## Risks & mitigations

- **Dusk requires a real Chrome binary** — CI must install Chromium. Locally, `vendor/bin/dusk-updater` is included with `laravel/dusk`. The release pipeline (Step 10) will pin Chrome via GitHub Actions.
- **`Comment::whenCreating()` static leaks across tests** — `tearDown()` in `CommentModelTest` calls `Comment::whenCreating(null)`. We accept that as a contract change vs. upstream.
- **Vue build step in CI** — adds `npm ci && npm run prod` to the test job (committed `dist/` is for consumers; in CI we always rebuild to detect drift).
