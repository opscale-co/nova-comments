# Feature Specification: nova-comments package

**Feature Branch**: `001-nova-comments`
**Created**: 2026-06-04
**Status**: Draft
**Input**: User description: "Polymorphic comments field for any Laravel Nova Resource, replicating kirschbaum-development/nova-comments under Opscale conventions."

## Context

`opscale-co/nova-comments` is a Laravel Nova 5 package that lets a Laravel developer drop polymorphic, threaded-per-resource comments into any Nova Resource. It replicates the behavior of [kirschbaum-development/nova-comments](https://github.com/kirschbaum-development/nova-comments) — the upstream package is the de facto reference for the surface area, copy text, and API contracts.

The "users" of this feature are **Laravel developers** integrating the package. The "operators" (people writing comments through the UI) are Nova authenticated users in the host app.

## User Scenarios & Testing

### User Story 1 — Add comments to a Resource (Priority: P1)

A developer adds the `Commenter` ResourceTool to a Nova Resource's `fields()` array and mixes the `Commentable` trait into the underlying Eloquent model. Authenticated Nova users then see a "Comments" panel on the resource detail page, can write a comment, hit Save (or ⌘+Enter), and see it appear in the list with author + relative date. They can paginate older / newer with the per-resource pagination controls.

**Why this priority**: This is the entire reason the package exists. Without it, the package is not viable.

**Independent Test**: Integrate the tool into a workbench Nova app with a `Post` resource using `Commentable`. Log in, navigate to `/resources/posts/{id}`, type into the textarea, click Save. The comment appears below the form, attributed to the authenticated user, and persists across page reloads. A Browser/Dusk test automates that flow end-to-end.

**Acceptance Scenarios**:

1. **Given** a Nova Resource with `Commenter::make()` in `fields()` and the underlying model using `Commentable`, **When** an authenticated user submits a non-empty comment on the resource detail page, **Then** the comment is persisted in the `nova_comments` table with `commentable_type` + `commentable_id` pointing at the host record and `commenter_id` set to the authenticated user's ID.
2. **Given** an existing record with multiple comments, **When** the user opens the detail page, **Then** comments are loaded ordered by `created_at DESC` and rendered with the commenter name + a humanized relative date.
3. **Given** more comments than fit in one page, **When** the user clicks **Older** / **Newer**, **Then** the panel paginates through the comment history without leaving the detail page.
4. **Given** the user is typing in the textarea, **When** they press ⌘+Enter (macOS) / Ctrl+Enter, **Then** the comment is submitted exactly as if they clicked **Save Comment**.
5. **Given** an empty textarea, **When** the user clicks Save, **Then** no request is sent and nothing happens.

---

### User Story 2 — Configure the commenter resource and navigation (Priority: P2)

The developer customizes the package via `config/nova-comments.php` (after publishing): they can point the commenter belongs-to relation at a custom Nova resource (e.g. `App\Nova\Member` instead of `App\Nova\User`), hide the Comment resource from the global Nova sidebar, change the panel display name, and tune the index-page comment-preview length.

**Why this priority**: Hosts rarely use the default `App\Nova\User` resource — most Opscale apps use a domain-specific actor. Without configurability, integrating is painful.

**Independent Test**: In the workbench, publish the config, set `commenter.nova-resource` to a custom Nova resource, and `available-for-navigation` to `false`. Verify the commenter link on each comment routes through the custom resource's `uriKey` and the Comments item disappears from the sidebar.

**Acceptance Scenarios**:

1. **Given** `nova-comments.commenter.nova-resource` is set to a custom Nova resource class, **When** a comment is rendered, **Then** the commenter name links to that resource (`/resources/{uriKey}/{id}`).
2. **Given** `nova-comments.available-for-navigation` is `false`, **When** Nova builds the sidebar, **Then** the Comment resource does not appear in it but `/nova-api/comments` still serves requests.
3. **Given** `nova-comments.limit` is `50`, **When** the Comment index page renders the truncated comment text, **Then** comments are truncated at 50 characters with an ellipsis.

---

### User Story 3 — Override sanitization (Priority: P3)

The developer registers a custom `Comment::whenCreating()` callback at boot time (e.g. in a service provider) to replace the default `strip_tags` + `FILTER_SANITIZE_SPECIAL_CHARS` sanitization — for example, to allow a curated Markdown subset.

**Why this priority**: A small but real subset of hosts wants markdown / rich text. Upstream supports this hook and consumers depend on it.

**Independent Test**: In a unit test, register `Comment::whenCreating(fn ($c) => $c->comment = strtoupper($c->comment))`, create a comment with body `"hello"`, assert the stored body is `"HELLO"` (i.e. the default sanitizer did NOT run).

**Acceptance Scenarios**:

1. **Given** `Comment::whenCreating($cb)` has been registered, **When** a Comment is created, **Then** `$cb` runs and the default sanitizer does NOT run.
2. **Given** no callback is registered, **When** a Comment is created with body `<script>alert(1)</script>hi`, **Then** the stored body is `&#38;lt;script&#38;gt;alert(1)&#38;lt;/script&#38;gt;hi` (tags stripped, special chars escaped).

---

### Edge Cases

- **Unauthenticated request hitting `POST /nova-api/comments`** — Nova's default resource policy gates this; the field's `creating` hook sets `commenter_id` only if `auth()->check()` is true, so the value remains `NULL` in unauthenticated contexts (e.g. system-generated comments via a job).
- **Host model uses non-integer primary keys (ULID)** — the polymorphic `commentable_id` column is `unsignedBigInteger` per upstream. Out-of-the-box this only works with int-keyed hosts; ULID-keyed hosts need to publish + adjust the migration. This is a documented limitation, not a defect, for v1.
- **Comment::whenCreating() persisted between tests** — the static callback is process-global; tests must reset it in `tearDown` to avoid leaking state.
- **Long comment bodies** — the column type is `TEXT`; no length validation server-side beyond the column limit (~64 KB on MySQL). The Vue textarea has no `maxlength`.
- **Empty whitespace-only comments** — the client guards against `comment === ''`, but does not trim. A body of `"   "` will reach the server.
- **XSS via the rendered comment string** — the `Comment.vue` component uses `v-html` to render the stored body. The default sanitizer is what prevents script injection; replacing it with `whenCreating()` is the developer's responsibility to keep safe.

## Requirements

### Functional Requirements

- **FR-001**: The package MUST expose a `Commenter` Nova `ResourceTool` that any Nova Resource can list inside its `fields()` method.
- **FR-002**: The package MUST expose a `Commentable` PHP trait that, when added to an Eloquent model, defines a `comments()` morphMany relation to the package's `Comment` model.
- **FR-003**: The package MUST register a Nova `Comment` resource so the existing Nova resource API (`/nova-api/comments`) handles list/create requests with `viaResource` + `viaResourceId` + `viaRelationship=comments`. No custom controller, no custom routes.
- **FR-004**: The package MUST ship a migration that creates a `nova_comments` table with: PK `id`, polymorphic `commentable_type` + `commentable_id` (indexed), nullable `commenter_id`, `comment` TEXT, and Laravel timestamps.
- **FR-005**: On `creating`, the Comment model MUST auto-populate `commenter_id` from `auth()->id()` when a user is authenticated, AND apply the default sanitizer (`strip_tags` + `FILTER_SANITIZE_SPECIAL_CHARS`) on `comment`, UNLESS a `whenCreating($cb)` override has been registered (in which case ONLY the callback runs).
- **FR-006**: The package MUST expose a publishable config file with: `available-for-navigation` (bool), `commenter.nova-resource` (class-string), `comments-panel.name` (string), `limit` (int).
- **FR-007**: The package MUST ship a Vue 3 panel that renders inside the Nova detail page (component name `commenter`) with a textarea, a Save Comment button, a comments list, ⌘/Ctrl+Enter submit, and paginated Older / Newer controls.
- **FR-008**: The package MUST expose a `CommentsPanel` convenience Panel class wrapping a `MorphMany` field for hosts that prefer the standard Nova relation panel UI.
- **FR-009**: The package MUST auto-register its service provider via Laravel package discovery (no manual `config/app.php` edit).
- **FR-010**: The package MUST publish its built JS/CSS under `dist/` and register them via `Nova::script` + `Nova::style` (handle `commentable`).
- **FR-011**: All PHP files in `src/` MUST `declare(strict_types=1)` and pass PHPStan level 8.
- **FR-012**: The package MUST ship Unit tests (model + trait), Feature tests (Nova resource API via testbench), and a Browser/Dusk test that exercises the panel in a real headless Chrome against the workbench Nova app.

### Key Entities

- **Comment** — a polymorphic note attached to any model. Attributes: `id`, `commentable_type`, `commentable_id`, `commenter_id` (nullable), `comment` (text), `created_at`, `updated_at`. Relations: `commentable` (morphTo, any model), `commenter` (belongsTo, configured user model).
- **Commentable host** — any Eloquent model in the host app that uses the `Commentable` trait. Owns a `comments()` morphMany.
- **Commenter** — the authenticated user resolved through `config('auth.providers.users.model')` at runtime. The package does not own this entity.

## Success Criteria

### Measurable Outcomes

- **SC-001**: A developer can integrate the package into a fresh Nova 5 app — `composer require`, `php artisan migrate`, add the trait, add the field — in under 5 minutes with no manual route/asset wiring.
- **SC-002**: Every test in the package's `tests/` suite (Unit + Feature + Browser) passes on a fresh `composer install` + `vendor/bin/testbench package:discover` against the workbench, in under 60 seconds total wall time for Unit + Feature and under 90 seconds for Browser.
- **SC-003**: The Browser test creates a comment via the actual rendered UI and observes the rendered comment appearing in the list — proving the JS bundle in `dist/` is wired correctly and the `/nova-api/comments` POST round-trip works end-to-end.
- **SC-004**: Behavior parity with upstream is verifiable by diffing surface: same field name, same panel name, same config keys, same component name (`commenter`), same migration table (`nova_comments`), same Vue UI text and submit shortcut.
- **SC-005**: PHPStan level 8 is clean across `src/`. Duster lint is clean across PHP + Vue.

## Assumptions

- **Nova version**: targeting Laravel Nova 5.4+. Nova 4 compatibility is out of scope; the upstream `^4.0|^5.0` constraint is dropped to `^5.4`.
- **PHP version**: `^8.2` (Opscale baseline).
- **User model**: hosts have a `User` model with integer (or `BigIncrements`) primary key — matching upstream's `unsignedInteger commenter_id`. ULID-keyed user models are not supported in v1.
- **Auth driver**: hosts use Nova's default session auth — `auth()->id()` returns the active user's ID inside the comment-creating hook.
- **Asset bundling**: `dist/` artifacts are committed so consumers do not run a JS build.
- **Workbench validation**: `orchestra/testbench` + `orchestra/testbench-dusk` are dev dependencies that provide the headless Nova app for tests.
- **Single-tenant**: the comments table carries no tenant column, matching the constitution.
