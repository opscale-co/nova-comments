# Tasks: nova-comments package

**Spec**: [spec.md](./spec.md) • **Plan**: [plan.md](./plan.md)
**Rule**: every implementation task is preceded by its test task. Test tasks marked **[T]** must be RED-then-green: they are written first and asserted to fail (because the production code doesn't exist yet), then made green by the matching impl task.

---

## Phase A — Project setup

- **A1 — Remove old `nova:tool` skeleton.** Delete `src/Tool.php`, `src/ToolServiceProvider.php`, `src/Http/Controllers/ToolController.php`, `src/Http/Middleware/Authorize.php`, `routes/api.php`, `routes/inertia.php`, `resources/js/pages/Tool.vue`, `resources/js/tool.js` (will be rewritten), `tests/ToolControllerTest.php`, `tests/TestCase.php` (will be rewritten), `config/nova-tools.php`, `webpack.mix.js` + `package.json` (will be rewritten), `publish.sh`.
- **A2 — Rewrite `composer.json`.** Drop `laravel/nova-devtool`. Add: `orchestra/testbench-dusk ^9.0` (brings testbench + dusk), `larastan/larastan ^2.10`, `mockery/mockery`. Keep `phpunit/phpunit ^10.5`, `tightenco/duster ^2.7`, `laravel/nova ^5.4`. Update `extra.laravel.providers` if class name changes (it stays `ToolServiceProvider`).
- **A3 — Write `phpunit.xml.dist`** with three suites: `Unit`, `Feature`, `Browser`.
- **A4 — Write `package.json` + `webpack.mix.js` + `nova.mix.js`** for Vue 3 + Nova mix.

## Phase B — Workbench scaffold

- **B1 — Create workbench directories**: `workbench/app/Models/`, `workbench/app/Nova/`, `workbench/app/Providers/`, `workbench/database/factories/`, `workbench/database/seeders/`, `workbench/database/migrations/`.
- **B2 — Workbench `Post` model + migration + factory + Nova resource** declaring `Commenter::make()` in `fields()` and using the `Commentable` trait on the model.
- **B3 — Workbench `User` factory + `NovaServiceProvider` + `WorkbenchServiceProvider`.**
- **B4 — `DatabaseSeeder`** creates 1 user + 3 posts.

## Phase C — Tests RED (write before impl)

- **C1 [T] — `tests/TestCase.php`** base class — extends `Orchestra\Testbench\TestCase`, registers `ToolServiceProvider` + the workbench Nova provider, uses SQLite in-memory.
- **C2 [T] — `tests/Unit/CommentModelTest.php`**. Cases:
  - `it_sets_commenter_id_from_authenticated_user_on_create`
  - `it_leaves_commenter_id_null_when_unauthenticated`
  - `it_strips_tags_and_escapes_special_chars_by_default`
  - `it_runs_the_when_creating_callback_instead_of_the_default_sanitizer`
  - `it_resets_when_creating_in_teardown` (assertion in `tearDown`)
  - `it_resolves_commenter_relation_against_configured_user_model`
  - `it_morphs_to_the_commentable`
- **C3 [T] — `tests/Unit/CommentableTraitTest.php`**. Cases:
  - `the_trait_exposes_a_morph_many_named_comments_pointing_at_the_comment_model`
  - `creating_comments_via_the_relation_stores_correct_morph_columns`
- **C4 [T] — `tests/Feature/CommentResourceApiTest.php`**. Cases:
  - `posting_to_nova_api_comments_creates_a_comment_attributed_to_the_authenticated_user`
  - `posting_with_via_resource_post_links_comment_to_the_right_post`
  - `getting_nova_api_comments_returns_comments_in_desc_created_at_order`
  - `unauthenticated_post_is_rejected_by_nova`
- **C5 [T] — `tests/Feature/ConfigurationTest.php`**. Cases:
  - `available_for_navigation_false_hides_comment_resource_from_navigation`
  - `nova_comments_limit_truncates_index_text`
  - `commenter_nova_resource_config_drives_belongs_to_field_target`
- **C6 [T] — `tests/Browser/DuskTestCase.php`** + `tests/Browser/CommenterToolTest.php`. Cases:
  - `it_renders_the_commenter_panel_on_the_post_detail_page`
  - `it_creates_a_comment_when_the_save_button_is_clicked`
  - `it_creates_a_comment_when_cmd_enter_is_pressed`
  - `it_does_not_submit_an_empty_comment`

Run `vendor/bin/phpunit` → all C2–C5 RED. Browser suite skipped at this point (no `dist/`).

## Phase D — Implement source — make C2–C5 green

- **D1 — `config/nova-comments.php`**.
- **D2 — `database/migrations/2026_06_04_000000_create_nova_comments_table.php`** — `bigIncrements`, `morphs('commentable')`, `unsignedBigInteger('commenter_id')->nullable()`, `text('comment')`, `timestamps()`.
- **D3 — `src/Models/Comment.php`** — table, `$fillable`, `boot()` with `creating` hook, `whenCreating()` static registrar (accepts callable|null), `commentable()`, `commenter()`.
- **D4 — `src/Commentable.php`** — trait, `comments(): MorphMany`.
- **D5 — `src/Nova/Comment.php`** — Nova resource.
- **D6 — `src/Commenter.php`** — `ResourceTool` with `name()` from config and `component() === 'commenter'`.
- **D7 — `src/CommentsPanel.php`** — wraps `MorphMany` field.
- **D8 — `src/ToolServiceProvider.php`** — boot order: register config → publish config → load migrations → register Nova resource → `Nova::serving` registers JS + CSS.

Run `vendor/bin/phpunit --exclude-testsuite=Browser` → all green.

## Phase E — Implement Vue panel — make C6 green

- **E1 — `resources/css/tool.css`** (empty placeholder).
- **E2 — `resources/js/tool.js`** — register `commenter` component.
- **E3 — `resources/js/components/Comment.vue`** — single-comment template (commenter link, relative date, `v-html` body).
- **E4 — `resources/js/components/Tool.vue`** — full panel with textarea, button, ⌘+Enter, paginated list, `dusk` selectors (`commenter-textarea`, `commenter-save`, `commenter-list`, `commenter-comment-{n}`).
- **E5 — `npm ci && npm run prod`** → produces `dist/js/tool.js` + `dist/css/tool.css`.
- **E6 — `vendor/bin/dusk-updater`** to install matching ChromeDriver.

Run `vendor/bin/phpunit --testsuite=Browser` → all green.

## Phase F — Quality gates

- **F1 — Larastan level 8** on `src/`. Fix anything raised.
- **F2 — Duster fix** on PHP + JS.
- **F3 — Final full suite.** `vendor/bin/phpunit` (all three suites) — must be green.

## Phase G — Documentation

- **G1 — Rewrite `README.md`** — install / migrate / usage examples / config reference / `whenCreating` example. Mention upstream attribution and `MIT`.
- **G2 — Rewrite `CHANGELOG.md`** with `0.1.0 — Initial Opscale release`.

---

## Dependency graph (informal)

```
A1 → A2 → A3 → A4
A1 → B1 → B2 → B3 → B4
B4 + A3 → C1 → (C2, C3, C4, C5 in parallel)
C2 + C3 → D3 → D4 → D7
C4 + C5 → D1, D2, D5, D6, D8
D8 → E1..E4 → E5 (build) → E6 (driver) → C6
all → F1 → F2 → F3 → G1, G2
```
