# Indexera — Docker Deployment

The Docker image runs Nginx and PHP 8.4-FPM inside a single container, supervised by runit via [Phusion Baseimage](https://github.com/phusion/baseimage-docker). The application expects an external database.

---

## Table of Contents

- [Building the Image](#building-the-image)
- [Configuration](#configuration)
  - [Option A — Environment Variables](#option-a--environment-variables)
  - [Option B — Mounted config.ini](#option-b--mounted-configini)
- [Running the Container](#running-the-container)
- [OAuth Configuration](#oauth-configuration)
- [Logging](#logging)
- [Volumes](#volumes)
- [Database Setup](#database-setup)
- [docker-compose](#docker-compose)

---

## Building the Image

```bash
docker build -t indexera:latest .
```

---

## Configuration

The app uses [`dealnews/get-config`](https://github.com/dealnews/get-config), which reads from either a `config.ini` file or environment variables. Environment variable names are the uppercased, underscore form of the config key:

```
db.indexera.type  →  DB_INDEXERA_TYPE
oauth.github.client_id  →  OAUTH_GITHUB_CLIENT_ID
```

### Option A — Environment Variables

Pass database config directly at `docker run` time:

```bash
docker run -d \
  -p 8000:80 \
  -e DB_INDEXERA_TYPE=mysql \
  -e DB_INDEXERA_SERVER=db.example.com \
  -e DB_INDEXERA_PORT=3306 \
  -e DB_INDEXERA_DB=indexera \
  -e DB_INDEXERA_USER=app \
  -e DB_INDEXERA_PASS=secret \
  indexera:latest
```

### Option B — Mounted config.ini

Mount a `config.ini` file into the container:

```bash
docker run -d \
  -p 8000:80 \
  -v /path/to/config.ini:/app/etc/config.ini:ro \
  indexera:latest
```

A template is available at `etc/config.ini.example`.

---

## Running the Container

Once configured, the app is available at [http://localhost:8000](http://localhost:8000).

The first account registered automatically becomes an admin.

**With MySQL:**

```bash
docker run -d \
  -p 8000:80 \
  -e DB_INDEXERA_TYPE=mysql \
  -e DB_INDEXERA_SERVER=your-db-host \
  -e DB_INDEXERA_PORT=3306 \
  -e DB_INDEXERA_DB=indexera \
  -e DB_INDEXERA_USER=app \
  -e DB_INDEXERA_PASS=secret \
  indexera:latest
```

**With PostgreSQL:**

```bash
docker run -d \
  -p 8000:80 \
  -e DB_INDEXERA_TYPE=pgsql \
  -e DB_INDEXERA_SERVER=your-db-host \
  -e DB_INDEXERA_PORT=5432 \
  -e DB_INDEXERA_DB=indexera \
  -e DB_INDEXERA_USER=app \
  -e DB_INDEXERA_PASS=secret \
  indexera:latest
```

---

## OAuth Configuration

OAuth providers are optional. Any combination can be enabled — providers with no `client_id` set are hidden from the UI automatically.

Pass credentials as environment variables:

| Provider | Environment Variables |
|----------|----------------------|
| GitHub | `OAUTH_GITHUB_CLIENT_ID`, `OAUTH_GITHUB_CLIENT_SECRET` |
| Google | `OAUTH_GOOGLE_CLIENT_ID`, `OAUTH_GOOGLE_CLIENT_SECRET` |
| Microsoft | `OAUTH_MICROSOFT_CLIENT_ID`, `OAUTH_MICROSOFT_CLIENT_SECRET` |

**Example — GitHub + Microsoft enabled:**

```bash
docker run -d \
  -p 8000:80 \
  -e DB_INDEXERA_TYPE=mysql \
  -e DB_INDEXERA_SERVER=db.example.com \
  -e DB_INDEXERA_DB=indexera \
  -e DB_INDEXERA_USER=app \
  -e DB_INDEXERA_PASS=secret \
  -e OAUTH_GITHUB_CLIENT_ID=your-github-client-id \
  -e OAUTH_GITHUB_CLIENT_SECRET=your-github-secret \
  -e OAUTH_MICROSOFT_CLIENT_ID=your-azure-client-id \
  -e OAUTH_MICROSOFT_CLIENT_SECRET=your-azure-secret \
  indexera:latest
```

See the main [README](../README.md#oauth-setup) for instructions on registering each OAuth application.

---

## Logging

Log destinations for Nginx and PHP-FPM are controlled via environment variables. Both services write to `/var/log/nginx` and `/var/log/php` by default — mount those paths as volumes to persist logs outside the container (see [Volumes](#volumes)).

### Nginx

| Variable | Default | Purpose |
|----------|---------|---------|
| `NGINX_ACCESS_LOG` | `/var/log/nginx/indexera-access.log` | Nginx access log path |
| `NGINX_ERROR_LOG` | `/var/log/nginx/indexera-error.log` | Nginx error log path |

### PHP-FPM

| Variable | Default | Purpose |
|----------|---------|---------|
| `PHP_ACCESS_LOG` | *(none — access logging disabled)* | PHP-FPM access log path |
| `PHP_ERROR_LOG` | `/var/log/php/www.error.log` | PHP-FPM error log path |

To redirect all logs to stdout/stderr for a logging driver or aggregator:

```bash
docker run -d \
  -p 8000:80 \
  -e NGINX_ACCESS_LOG=/dev/stdout \
  -e NGINX_ERROR_LOG=/dev/stderr \
  -e PHP_ACCESS_LOG=/dev/stdout \
  -e PHP_ERROR_LOG=/dev/stderr \
  ...
  indexera:latest
```

---

## Volumes

| Path | Purpose |
|------|---------|
| `/app/etc` | Config directory — mount `config.ini` here |
| `/var/log/nginx` | Nginx access and error logs |
| `/var/log/php` | PHP-FPM access and error logs |

**Persisting logs example:**

```bash
docker run -d \
  -p 8000:80 \
  -v /path/to/config.ini:/app/etc/config.ini:ro \
  -v /var/log/indexera/nginx:/var/log/nginx \
  -v /var/log/indexera/php:/var/log/php \
  indexera:latest
```

---

## Database Setup

The container does not run database migrations. Apply the schema to your database before starting the container for the first time:

```bash
# MySQL
mysql -h your-db-host -u app -p indexera < schema/mysql.sql

# PostgreSQL
psql -h your-db-host -U app indexera < schema/postgresql.sql
```

---

## docker-compose

A minimal `docker-compose.yml` is included in the repo root for deployments using a mounted config file:

```yaml
services:
  indexera:
    image: dealnews/indexera:latest
    ports:
      - "8000:80"
    volumes:
      - ./etc/config.ini:/app/etc/config.ini:ro
```

```bash
# Start
docker compose up -d

# Stop
docker compose down

# View logs
docker compose logs -f
```
