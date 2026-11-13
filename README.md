# Nested Forum Replies Lab (PHP, MySQL, PDO)

A small forum that shows replies as a tree. Anyone can read; only logged-in users can post.

## Libraries used

| Part | Library | Why |
|---|---|---|
| Router | **Bramus Router** (`bramus/router`) | Lecture library, simple regex routes |
| Templates | **Mustache** (`mustache/mustache`) | Lecture library, escapes `{{ }}` output by default |
| Database | **PDO** (built into PHP) | Used instead of Eloquent to keep it simple. All SQL is inside `app/Models/` with bound parameters. |

## Requirements
PHP 8.2+ with `pdo_mysql`, MySQL 8 (or MariaDB 10.5+), Composer.

## Setup (5 steps)

```bash
# 1. Install the libraries
composer install

# 2. Create the database, tables and fictional seed data
mysql -u root -p < database/schema.sql

# 3. Make your local config (this file is git-ignored, so your password is never committed)
cp config/config.example.php config/config.php
#    then edit config/config.php and set your MySQL user and password

# 4. Start the local server
php -S localhost:8000 -t public public/index.php

# 5. Open http://localhost:8000/discussions/1
```

> Windows (XAMPP): use `copy` instead of `cp`, and run `mysql` from `C:\xampp\mysql\bin` (or import `database/schema.sql` in phpMyAdmin).

The seed users (Alice, Ben, Cara, Dee, Eli) have no password, so they **cannot** log in. Click **Register** to create your own account.

## Routes

| Method | URL | What it does |
|---|---|---|
| GET | `/` | List discussions |
| GET | `/discussions/{id}` | Show a discussion and its reply tree |
| GET/POST | `/register` | Create an account |
| GET/POST | `/login` | Log in |
| POST | `/logout` | Log out |
| POST | `/discussions/{id}/comments` | New top-level comment (login required) |
| POST | `/discussions/{id}/comments/{parentId}/replies` | Reply to a comment (login required) |

**ID responses:** a malformed ID like `/discussions/abc` returns **400**; a valid ID that doesn't exist like `/discussions/999` returns **404** (the comment query is not run in either case).

## How a request flows

```
Browser → public/index.php (front controller)
        → Bramus Router (config/routes.php)
        → Controller (validates ID, checks login + CSRF for POST)
        → Model (PDO prepared statements in app/Models)
        → CommentTree (groups comments by parent_id, calculates depth)
        → Mustache view (app/Views, escapes all text) → HTML response
```

## Data model in one paragraph
`discussion_id` says which discussion a comment belongs to. `parent_id` says which comment it replies to (`NULL` = top-level). Depth is **calculated**, not stored. A composite foreign key `(discussion_id, parent_id) → comments(discussion_id, id)` makes sure a reply's parent is in the same discussion. Cycles can't happen because parents are set once on insert (there is no edit) and must already exist; the renderer also has a visited-check and a node limit.

## Security summary
- Passwords: `password_hash` / `password_verify`; a NULL hash never logs in.
- Session: `session_regenerate_id(true)` on login; cookie is HttpOnly, SameSite=Lax, and Secure on HTTPS.
- CSRF token on every POST form (register, login, logout, comments), checked with `hash_equals`.
- Every write checks login on the server. `user_id` always comes from the session, never the form.
- Parent must be in the same discussion; max reply depth 20; comment length 1–2000.
- All output uses Mustache `{{ }}` (escaped). No `{{{ }}}` is used.

## Tests
```bash
php tests/CommentTreeTest.php      # automatic: tree, depth, indent cap, cycle guard, limit
```
Manual / curl checks are listed in `tests/manual-checks.md`.

## Production note (explanation only)
On Apache, point the document root to `public/` and send every non-file request to `index.php`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```
On Nginx: `try_files $uri /index.php?$query_string;`

## Optional challenge (done)
`public/js/replies.js` adds accessible **Hide replies / Show replies** buttons (`<button type="button">`, `aria-controls`, `aria-expanded`, `hidden`). Buttons only appear after the script runs, so with JavaScript off all replies stay visible.
