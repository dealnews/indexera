# Indexera — AI Agent Guide

A link-sharing app built in PHP. Users create pages of curated links, organize them into sections, make pages public or private, and subscribe to others' pages. There's a REST API for all CRUD operations and a full web UI built on Materialize CSS.

---

## Project Overview

- **Language:** PHP 8.2+, `declare(strict_types=1)` in every file
- **Database:** MySQL, PostgreSQL, or SQLite (all supported)
- **Frontend:** Materialize CSS + Alpine.js (both CDN), no build step
- **Auth:** Session-based + OAuth2 (GitHub, Google, Microsoft)
- **Entry point:** `public/index.php` — routing, session setup, and dispatch all happen here

---

## Architecture & Design

### Request Flow

```
public/index.php
  └─ PageMill\Router → matches route → Controller::handleRequest()
       ├─ BaseController loads session user + settings
       ├─ Auth/admin guards redirect if needed
       ├─ filterInput() → getRequestActions() → buildModels() → getResponder()
       └─ HtmlResponder maps $data keys to View public properties → renders HTML
```

For API routes (`/api/*`), the flow goes through `src/Api.php` instead, which routes to `Api\Action\*` classes that return JSON.

### Key Layers

| Layer | Location | Purpose |
|-------|----------|---------|
| Controllers | `src/Controller/` | HTTP dispatch, auth guards, wiring models + responders |
| Actions | `src/Action/` | Form submission business logic |
| Models | `src/Model/` | Data preparation for views |
| Views | `src/View/` | HTML rendering |
| API Actions | `src/Api/Action/` | REST CRUD with auth + ownership |
| Data | `src/Data/` | Value objects (one per table) |
| Mappers | `src/Mapper/` | ORM — maps objects ↔ database rows |
| Repository | `src/Repository.php` | Service locator for all mappers |

### BaseController

Every HTML controller extends `BaseController`. Key properties:

```php
protected bool $require_auth = false;      // redirect to /login if unauthenticated
protected bool $require_admin = false;     // 403 + redirect if not admin
protected bool $build_models_first = false; // run buildModels() before getResponder()
```

`loadCurrentUser()` runs in the constructor and always populates:
- `$this->current_user` — the authenticated `User` object or `null`
- `$this->data['settings']` — the singleton `Settings` row
- `$this->data['oauth_github/google/microsoft']` — bool flags from config

### Data Flow to Views

Views have typed public properties. The `PropertyMap` trait in `pagemill/mvc` maps matching keys from `$this->data` to view properties by name. So if you add `$this->data['foo'] = 'bar'` in a controller or model, and the view has `public string $foo = ''`, it just works.

### Repository & Mappers

```php
$repository = new Repository();

$user = $repository->get('User', 42);           // fetch by PK
$pages = $repository->find('Page', [            // find by criteria
    'user_id' => 5,
    'is_public' => true,
], order: 'title', limit: 10);
$user = $repository->save('User', $user);       // INSERT if PK=0, UPDATE otherwise
$repository->delete('Page', $page_id);
```

`find()` returns a PK-keyed associative array — use `array_values()` if you need a plain list (e.g., before JSON-encoding for the frontend).

### REST API

All API routes live under `/api/{ObjectName}`. The custom actions in `src/Api/Action/` enforce:
- **Authentication** — `AuthTrait` returns 401 if no session
- **Ownership** — `OwnershipTrait` traces foreign keys up to `user_id` and returns 403 if mismatch
- **Field allowlisting** — `UpdateObject` restricts which fields non-admins can set
- **Password security** — hashed on write, stripped from all responses

`Content-Type: application/json` is set in `index.php` before API execution, which is required for `@dealnews/data-mapper-client` to parse responses.

### Settings (Singleton)

One row in the `settings` table, always `settings_id = 1`. Loaded on every request in `BaseController::loadCurrentUser()`. Access in any view via `$this->settings`. Current fields:

| Field | Type | Default | Purpose |
|-------|------|---------|---------|
| `site_title` | string | Indexera | HTML `<title>` prefix |
| `nav_heading` | string | Indexera | Nav bar brand text |
| `public_pages` | bool | true | Guests can view pages |
| `allow_registration` | bool | true | New account creation open |

When adding a new setting: update `Settings.php`, `SettingsMapper.php` (MAPPING), all three schema files, `SettingsView.php`, `SaveSettingsAction.php`, and run the `ALTER TABLE` on the dev DB.

### OAuth2

Provider trait builds the correct `league/oauth2-*` provider from config. Callback controller:
1. Validates CSRF state with `hash_equals()`
2. Looks up existing `UserIdentity` by provider + provider UID
3. If none found, checks for existing `User` by email and links the identity
4. If no email match, creates a new user (first user ever becomes admin)

---

## Coding Standards

This project follows DealNews PHP conventions. Key rules:

### Braces — 1TBS

```php
// Correct
public function doSomething(): void {
    if ($condition) {
        foo();
    }
}

// Multi-line condition: closing paren + brace on its own line
if ($really &&
    $long &&
    $condition)
{
    foo();
}
```

### Naming

- Variables and properties: `snake_case` (`$user_id`, `$display_name`)
- Classes: `PascalCase`, PSR-4 autoloading
- Constants: `UPPER_SNAKE_CASE`

### Visibility

- Default to `protected`, not `private`, unless there's a specific reason
- Always declare property types: `public string $email = ''`

### Single Return Point

Prefer one `return` at the end. Early returns are fine for guard clauses/validation. Avoid multiple returns mid-logic.

```php
// Preferred
public function process(): bool {
    $result = false;
    if ($valid) {
        $result = $this->doWork();
    }
    return $result;
}
```

### Arrays

Short syntax only. Align values in associative arrays. Trailing commas on multi-line.

```php
$mapping = [
    'user_id'  => [],
    'email'    => [],
    'is_admin' => [],
];
```

### PHPDoc

Every class and every method needs a docblock. Include `@param`, `@return`, `@throws` where applicable. Class-level block describes purpose and package.

```php
/**
 * Loads the authenticated user's pages and subscriptions.
 *
 * @package Dealnews\Indexera\Model
 */
class UserPagesModel extends ModelAbstract {

    /**
     * Returns pages owned by and subscribed to by the current user.
     *
     * @return array
     */
    public function getData(): array {
```

### Strings

Multi-line string concatenation — align the dot:

```php
$message = "This is the first part. " .
           "This is the second part.";
```

### No Heredoc

Don't use heredoc syntax. Use concatenation or inline PHP in template files.

### Strict Types

Always include `declare(strict_types=1)` in new files.

---

## Build & Test

```bash
# Install dependencies
composer install

# Run the dev server (from repo root)
php -S localhost:8080 -t public/

# There is no automated test suite yet.
# Static analysis and test infrastructure are future work.
```

**Configuration:**

```bash
cp etc/config.ini.example etc/config.ini
# Edit etc/config.ini with your DB DSN and OAuth credentials
```

**Dev database setup (SQLite):**

```bash
sqlite3 .dev/dev.db < schema/sqlite.sql
```

---

## Common Patterns

### Adding a New Controller

1. Create `src/Controller/FooController.php` extending `BaseController`
2. Implement `getModels(): array` (return `[]` if no models needed)
3. Implement `getResponder(): ResponderAbstract` returning `new HtmlResponder(FooView::class)`
4. Add the route in `public/index.php`

```php
class FooController extends BaseController {

    protected bool $require_auth = true;

    protected function getModels(): array {
        return [FooModel::class];
    }

    protected function getResponder(): ResponderAbstract {
        return new HtmlResponder(FooView::class);
    }
}
```

### Adding a New Setting

1. Add typed property to `src/Data/Settings.php`
2. Add column name to `MAPPING` in `src/Mapper/SettingsMapper.php`
3. Add column to all three schema files (`schema/sqlite.sql`, `mysql.sql`, `postgresql.sql`)
4. Add checkbox/input to `src/View/Admin/SettingsView.php`
5. Add `public string $field = '0'` to `src/Action/Admin/SaveSettingsAction.php` and save it
6. Migrate the dev DB: `sqlite3 .dev/dev.db "ALTER TABLE settings ADD COLUMN ..."`

### Building Models Before the Responder

When `getResponder()` needs to branch on model data (e.g., 404 vs. normal view), set:

```php
protected bool $build_models_first = true;
```

Then check `$this->data` inside `getResponder()`.

### API + Alpine.js on the Frontend

The page editor uses `@dealnews/data-mapper-client` (CDN) with Alpine.js for reactive UI. Key gotchas:
- `x-data` attribute values must be HTML-safe: `htmlspecialchars(json_encode($data), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8')`
- `find()` returns PK-keyed arrays; use `array_values()` before JSON-encoding for the frontend
- The client only parses response bodies when `Content-Type: application/json` is present — this is set globally in `index.php`

---

## Data Model Quick Reference

| Object | Owned by | Notable |
|--------|----------|---------|
| `Page` | `user_id` | `slug` unique per user; `is_public` controls visibility |
| `Section` | → Page → user | `sort_order` controls display sequence |
| `Link` | → Section → Page → user | `sort_order` controls display sequence |
| `PageSubscription` | `user_id` | Junction table; can't subscribe to own page |
| `UserIdentity` | `user_id` | One row per OAuth provider connection |
| `Settings` | — | Singleton, always `settings_id = 1` |

---

## Gotchas & Edge Cases

- **`find()` returns PK-keyed arrays.** `reset($results)` to get the first item. `array_values($results)` before sending to JS.
- **Settings default to `true`.** The `Settings` value object defaults `public_pages` and `allow_registration` to `true`. If no row exists in the DB, `BaseController` falls back to `new Settings()`, so the site stays open by default.
- **First registered user becomes admin.** Both `RegisterAction` and `OAuthCallbackController` check `empty($repository->find('User', [], limit: 1))` before saving the new user.
- **OAuth email matching.** If a user authenticates via OAuth and their email matches an existing account, the OAuth identity is linked to that account rather than creating a duplicate.
- **Password is `null` for OAuth-only users.** Don't assume `$user->password` is set. The login form path guards against this.
- **`updated_at` is read-only in mappers.** It's managed by DB triggers. Setting it in PHP does nothing.
- **`build_models_first` controllers don't call `parent::handleRequest()` the same way.** They manually run the pipeline in the right order — see `BaseController::handleRequest()` for the sequence.
- **Admin check in `BaseAdminController`.** Both `$require_auth = true` and `$require_admin = true` are set. Non-admins get a 403 redirect to `/dashboard`, not a 401.

---

## Making Changes

### Adding a Feature

1. **Database change?** Update the relevant `Data` class, its `Mapper`, and all three schema files. Migrate the dev DB manually.
2. **New route?** Add controller + view + route entry in `public/index.php`.
3. **New setting?** Follow the six-step settings checklist above.
4. **New API capability?** The API actions in `src/Api/Action/` handle object-level logic; add cases there rather than new action classes when possible.

### Fixing a Bug

- Check the controller to confirm routing and auth guards are correct
- Check the model if data looks wrong
- Check the mapper `MAPPING` if fields aren't persisting
- Check `OwnershipTrait` if API calls return 403 unexpectedly
- Check that `array_values()` is used if JS receives an object instead of an array

### Schema Changes in Production

The project has no migration tool. Schema changes must be applied manually. Always update all three schema files (`sqlite.sql`, `mysql.sql`, `postgresql.sql`) so they stay as the authoritative source of truth.
