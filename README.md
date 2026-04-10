# Indexera

A self-hosted link-sharing application. Create pages of curated links, organize them into sections, share them publicly or keep them private, and subscribe to pages from other users.

[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-8.2%2B-8892BF.svg)](https://php.net)

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the App](#running-the-app)
- [OAuth Setup](#oauth-setup)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)

---

## Features

- **Link pages** — Create named pages with sections of labelled links
- **Public & private pages** — Control visibility per page; optionally lock the whole site behind login
- **Subscriptions** — Follow other users' public pages from your dashboard
- **OAuth login** — Sign in with GitHub, Google, or Microsoft in addition to email/password
- **Admin panel** — Manage users, toggle admin privileges, and configure site settings
- **Multi-database** — Runs on MySQL, PostgreSQL, or SQLite

---

## Requirements

- PHP 8.2+
- One of: MySQL 8+, PostgreSQL 13+, or SQLite 3
- Composer
- A web server (Apache, Nginx, or PHP's built-in server for development)

---

## Installation

```bash
git clone https://github.com/dealnews/indexera.git
cd indexera
composer install
```

---

## Configuration

Copy the example config and fill in your values:

```bash
cp etc/config.ini.example etc/config.ini
```

**Minimum required config (`etc/config.ini`):**

```ini
; SQLite (quickest for local dev)
db.indexera.type = pdo
db.indexera.dsn  = sqlite:/absolute/path/to/indexera.db

; MySQL
; db.indexera.type   = mysql
; db.indexera.server = 127.0.0.1
; db.indexera.port   = 3306
; db.indexera.db     = indexera
; db.indexera.user   = root
; db.indexera.pass   =

; PostgreSQL
; db.indexera.type   = pgsql
; db.indexera.server = 127.0.0.1
; db.indexera.port   = 5432
; db.indexera.db     = indexera
; db.indexera.user   = postgres
; db.indexera.pass   =
```

Config values can also be set as environment variables using the uppercased, underscore form of the key:

```
OAUTH_GITHUB_CLIENT_ID=abc123
```

---

## Database Setup

Run the schema for your chosen database:

```bash
# SQLite
sqlite3 /path/to/indexera.db < schema/sqlite.sql

# MySQL
mysql -u root indexera < schema/mysql.sql

# PostgreSQL
psql -U postgres indexera < schema/postgresql.sql
```

The schema creates all tables and seeds the `settings` row. No migration tool is included — apply `ALTER TABLE` statements manually when upgrading.

---

## Running the App

### Development (PHP built-in server)

```bash
php -S localhost:8080 -t public/
```

Then open [http://localhost:8080](http://localhost:8080).

The first account you register automatically becomes an admin.

### Production

Point your web server's document root at `public/`. All requests that don't match a static file should be rewritten to `public/index.php`.

**Nginx example:**

```nginx
server {
    root /var/www/indexera/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Apache (`.htaccess` in `public/`):**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

---

## OAuth Setup

OAuth providers are optional. Any combination can be enabled; providers with no `client_id` configured are hidden from the UI automatically.

### GitHub

1. Go to **GitHub → Settings → Developer settings → OAuth Apps → New OAuth App**
2. Set **Authorization callback URL** to `https://yourhost/auth/github/callback`
3. Add to `etc/config.ini`:

```ini
oauth.github.client_id     = your-client-id
oauth.github.client_secret = your-client-secret
```

### Google

1. Go to **Google Cloud Console → APIs & Services → Credentials → Create credentials → OAuth client ID**
2. Set the **Authorized redirect URI** to `https://yourhost/auth/google/callback`
3. Add to `etc/config.ini`:

```ini
oauth.google.client_id     = your-client-id
oauth.google.client_secret = your-client-secret
```

### Microsoft (Azure / Entra ID)

1. Go to **Azure Portal → Azure Active Directory → App registrations → New registration**
2. Set the **Redirect URI** to `https://yourhost/auth/microsoft/callback`
3. Under **API permissions**, add `User.Read` (Microsoft Graph)
4. Add to `etc/config.ini`:

```ini
oauth.microsoft.client_id     = your-client-id
oauth.microsoft.client_secret = your-client-secret
```

If a user authenticates via OAuth and their email matches an existing account, the OAuth identity is linked to that account automatically rather than creating a duplicate.

### HTTPS Behind a Proxy or Load Balancer

OAuth redirect URIs must use `https`. If you run PHP behind an HTTPS load balancer or reverse proxy with an HTTP backend, PHP won't see `$_SERVER['HTTPS']` and will construct `http://` callback URLs, causing provider errors.

Set the `FORCE_HTTPS` environment variable to tell Indexera to always use `https` when building OAuth redirect URIs:

```sh
FORCE_HTTPS=1
```

How you set this depends on your environment. For example, in an Apache `VirtualHost`:

```apache
SetEnv FORCE_HTTPS 1
```

Or in a systemd service unit:

```ini
[Service]
Environment=FORCE_HTTPS=1
```

---

## Tech Stack

| Component | Package |
|-----------|---------|
| MVC framework | `pagemill/mvc` |
| Router | `pagemill/router` |
| ORM / data mapper | `dealnews/db`, `dealnews/data-mapper-api` |
| Value objects | `moonspot/value-objects` |
| Config | `dealnews/get-config` |
| OAuth2 | `league/oauth2-client` + GitHub, Google, Microsoft providers |
| CSS | [Materialize 1.0](https://materializecss.com) (CDN) |
| JS | [Alpine.js](https://alpinejs.dev) (CDN) |

---

## Project Structure

```
├── etc/                  # Configuration (config.ini.example → config.ini)
├── public/               # Web root (document root for your server)
│   ├── index.php         # Entry point — routing and request dispatch
│   └── css/app.css       # Custom styles
├── schema/               # Database schemas for SQLite, MySQL, PostgreSQL
└── src/
    ├── Action/           # Form submission handlers (login, register, profile, admin)
    ├── Api/              # REST API actions (CRUD with auth + ownership)
    ├── Controller/       # HTTP request handlers
    ├── Data/             # Value objects — one class per database table
    ├── Mapper/           # ORM mappers — object ↔ database row
    ├── Model/            # Data preparation for views
    ├── Responder/        # Response envelope (HTML)
    ├── View/             # HTML template classes
    ├── Api.php           # API route definitions
    ├── Repository.php    # Central mapper registry
    └── SessionHandler.php # Database-backed PHP session handler
```

---

## License

BSD 3-Clause. See [LICENSE](LICENSE).
