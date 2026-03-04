# Gong Cha Australia — CMS Web Application

A lightweight, self-contained Content Management System (CMS) for the Gong Cha Australia brand website. Built with PHP, MySQL, and vanilla HTML/CSS/JS — no frameworks, no build tools, no dependencies beyond a LAMP/XAMPP stack.

---

## Table of Contents

1. [Design Principles](#1-design-principles)
2. [Architecture Decision Records (ADR)](#2-architecture-decision-records-adr)
3. [Product Requirements Document (PRD)](#3-product-requirements-document-prd)
4. [System Design](#4-system-design)
5. [CMS & CRUD Reference](#5-cms--crud-reference)

---

## 1. Design Principles

### 1.1 Principles

**Simplicity over abstraction**
Every page is a single PHP file that handles its own routing, data access, and rendering. There are no controllers, no service layers, no dependency injection containers. A developer who knows basic PHP can read any file top-to-bottom and understand exactly what it does.

**Content ownership by non-technical staff**
The admin panel is designed so that a store manager — not a developer — can update menu items, prices, images, and About page copy without touching code or files directly.

**Brand fidelity**
The public-facing site mirrors the real Gong Cha Australia visual identity: Avenir typeface, the brand's dark brown / cream / red palette, and the same photography style used in official marketing materials.

**Security by default on the admin boundary**
The public site has no authentication surface. All write operations are behind a session-guarded admin panel with bcrypt passwords and brute-force lockout. The public site is read-only from a database perspective.

**Minimal footprint**
No Composer packages, no npm, no JavaScript framework. The entire project can be zipped, dropped onto any XAMPP/LAMP server, and run immediately. This keeps onboarding time near zero and eliminates supply-chain risk from third-party packages.

### 1.2 Values

| Value | Expression in this codebase |
|---|---|
| Readability | Procedural PHP, no magic, no metaprogramming |
| Portability | Runs on any standard LAMP stack without configuration |
| Maintainability | Small surface area — 11 PHP files total |
| Brand consistency | Shared `includes/` partials for navbar and footer |
| Security | bcrypt, prepared statements on auth, `htmlspecialchars` throughout |

### 1.3 Constraints

- **PHP procedural only** — no OOP, no MVC framework (Laravel, Symfony, etc.)
- **No JavaScript framework** — no React, Vue, or Alpine.js
- **Single admin role** — no RBAC; you are either logged in or you are not
- **Local development only** — DB credentials are hardcoded (`root`, no password); not production-ready without a secrets strategy
- **No REST API** — all data access is server-side rendered; no JSON endpoints
- **No automated tests** — no PHPUnit, no browser tests

---

## 2. Architecture Decision Records (ADR)

### ADR-001 — No PHP Framework

**Status:** Accepted

**Context**
The project is a small CMS for a single brand with two public pages and five admin pages. The team building it is learning PHP fundamentals. Introducing a framework (Laravel, CodeIgniter) would add significant boilerplate, a learning curve, and Composer dependency management overhead.

**Decision**
Use procedural PHP with direct `mysqli_*` calls. Each page file handles its own request lifecycle: session check → data mutation → data query → HTML render.

**Consequences**
- Positive: Zero setup beyond XAMPP. Any PHP developer can read the code immediately. No framework version lock-in.
- Negative: No routing, no ORM, no middleware pipeline. As the project grows, adding pages requires copy-pasting the session guard and DB connection boilerplate. SQL injection protection must be applied manually per query.

---

### ADR-002 — Key-Value Store for About Page Content

**Status:** Accepted

**Context**
The About page has three sections, each with a title, body text, an image, and an image alt attribute — 13 fields in total. These fields change independently and infrequently. Two design options were considered:

1. A single row in a typed table (`about_content` with 13 named columns).
2. A key-value table (`field_key VARCHAR, field_value TEXT`).

**Decision**
Use the key-value pattern (option 2). The table schema is `(id, field_key UNIQUE, field_value TEXT)`. Values are upserted with `INSERT ... ON DUPLICATE KEY UPDATE`.

**Consequences**
- Positive: Adding a new content field requires no schema migration — just use a new key. The admin form and public page can both be updated in PHP alone.
- Negative: No type safety on values (everything is TEXT). Querying a specific field requires a `WHERE field_key = '...'` lookup or a full table scan followed by PHP-side array building. Typos in key names cause silent missing content.

---

### ADR-003 — Session-Based Authentication (No JWT)

**Status:** Accepted

**Context**
The admin panel needs to restrict access to authenticated users. Options considered: PHP sessions, JWT stored in a cookie, HTTP Basic Auth.

**Decision**
Use PHP native sessions (`$_SESSION`). On successful login, set `$_SESSION['admin_logged_in'] = true`. Every admin page checks this flag at the top and redirects to `login.php` if absent.

**Consequences**
- Positive: No token library needed. Sessions are invalidated server-side on logout. Works out of the box with PHP's session GC.
- Negative: Sessions are tied to a single server (not horizontally scalable without a shared session store). CSRF protection is not implemented — form submissions are not validated with a CSRF token.

---

### ADR-004 — File Uploads Stored on Disk (Not in Database)

**Status:** Accepted

**Context**
Menu item images and About page images need to be stored somewhere. Options: store as BLOBs in MySQL, or store files on disk and save the filename in the DB.

**Decision**
Store uploaded files in `assets/uploads/`. Save only the filename (not the full path) in the database. The public URL is constructed at render time using the `ASSETS_URL` constant.

**Consequences**
- Positive: Simple. Images are served directly by Apache with no PHP overhead. Easy to inspect uploads via the filesystem.
- Negative: `assets/uploads/` is web-accessible — any file placed there is publicly reachable. No CDN, no image resizing, no storage quota enforcement. Backups must include the filesystem as well as the database dump.

---

### ADR-005 — Brute-Force Lockout on Admin Login

**Status:** Accepted

**Context**
The admin login form is publicly accessible at `/cmsweb/admin/login.php`. Without rate limiting, an attacker can attempt unlimited password guesses.

**Decision**
Track `failed_attempts` and `last_failed_login` in the `admin_creds` table. After 3 failed attempts within a 15-minute window, set `locked_until = NOW() + INTERVAL 10 MINUTE`. Reject all login attempts (even correct ones) until the lockout expires. Use the same error message for "unknown username" and "wrong password" to prevent user enumeration.

**Consequences**
- Positive: Significantly raises the cost of a brute-force attack. Anti-enumeration message prevents username harvesting.
- Negative: A legitimate admin who forgets their password is locked out for 10 minutes. There is no self-service password reset — a developer must manually clear `locked_until` in the database.

---

### ADR-006 — Separate CSS Files for Public and Admin

**Status:** Accepted

**Context**
The public site and the admin panel have completely different visual languages. Sharing a single CSS file would create specificity conflicts and make both harder to maintain.

**Decision**
Maintain two separate stylesheets: `assets/css/style.css` for the public site (BEM-style `.gc-*` classes) and `assets/css/admin.css` for the admin panel (`.adm-*` classes). Each is only loaded by its respective pages.

**Consequences**
- Positive: Zero risk of style bleed between public and admin. Each file can be iterated independently.
- Negative: Some utility patterns (buttons, alerts) are duplicated across both files.

---

## 3. Product Requirements Document (PRD)

### 3.1 Overview

The Gong Cha Australia CMS is a two-layer web application:

- **Public website** — a brand-accurate, database-driven website with an About Us page and a Tea Menu page.
- **Admin panel** — a password-protected back-office for managing all content shown on the public site.

The target users of the admin panel are Gong Cha Australia staff (store managers, marketing team) who need to update menu items and brand copy without developer involvement.

---

### 3.2 Feature: Public About Us Page

**Path:** `/cmsweb/public/about.php`

**Description**
A three-section brand storytelling page. All text and images are stored in the database and editable via the admin panel.

**Sections**

| Section | Content |
|---|---|
| Our Story | Brand origin story with a full-width image, headline, and two paragraphs of body text |
| Footprint in Numbers | Section headline, body text, and a supporting image |
| Journey in Australia | Section headline, body text, and a supporting image |

**Behaviour**
- All content is fetched from the `about_content` table on every page load.
- If a field is missing from the database, the section renders with an empty value (no error).
- Images are displayed using the stored filename resolved against `ASSETS_URL`.
- Legacy image paths (from earlier directory structures) are transparently rewritten at render time.

**Non-functional requirements**
- Page must render correctly on mobile (responsive layout via CSS).
- Shared navbar and footer are included from `includes/`.

---

### 3.3 Feature: Public Tea Menu Page

**Path:** `/cmsweb/public/menu.php`

**Description**
A filterable product catalogue displaying all menu items grouped by category. Users can browse categories via a sidebar navigation.

**Behaviour**
- On load, defaults to displaying the "Top 10" category (`?cat=top10`).
- The URL parameter `?cat=<slug>` controls which category is shown.
- The sidebar lists all 9 categories with their display names.
- The active category is visually highlighted.
- Products are displayed as cards: image, name, and price.
- If a product has no image, a placeholder is shown.

**Categories**

| Slug | Display Name |
|---|---|
| `top10` | Top 10 |
| `brewed` | Brewed Tea |
| `creative` | Creative Mix |
| `health` | Health Tea |
| `milkfoam` | Milk Foam |
| `milktea` | Milk Tea |
| `smoothie` | Smoothie |
| `yoghurt` | Yoghurt |
| `toppings` | Toppings |

**Non-functional requirements**
- Category filter must work without JavaScript (URL-based, server-side filtering).
- Product images are served from `assets/uploads/`.

---

### 3.4 Feature: Admin Login

**Path:** `/cmsweb/admin/login.php`

**Description**
A secure login form that grants access to the admin panel.

**Behaviour**
- Accepts a username and password.
- Verifies the password against a bcrypt hash stored in `admin_creds`.
- On success: creates a PHP session and redirects to `admin/menu.php`.
- On failure: increments `failed_attempts`. After 3 failures within 15 minutes, locks the account for 10 minutes.
- During lockout: all login attempts are rejected with a lockout message showing the remaining wait time.
- Error messages do not distinguish between "username not found" and "wrong password".
- Already-authenticated users visiting the login page are redirected to `admin/menu.php`.

**Security requirements**
- Passwords must be stored as bcrypt hashes (never plaintext).
- The login query must use a prepared statement.
- Lockout state is stored in the database (survives server restarts).

---

### 3.5 Feature: Admin Menu Management

**Path:** `/cmsweb/admin/menu.php`

**Description**
The primary admin screen for managing the tea menu. Displays all products and provides tools to add, edit, and delete items.

**Behaviour — Dashboard Stats**
- Shows total number of menu items.
- Shows number of distinct categories that have at least one item.

**Behaviour — Add New Item**
- Form fields: Name (text), Price (text), Category (dropdown of 9 options), Image (file upload, optional).
- On submit: validates that Name and Price are not empty; saves to `products` table; saves image to `assets/uploads/` with a randomised filename.
- Displays a success or error flash message after submission.

**Behaviour — Category Filter Tabs**
- Tabs for each category that has at least one item, plus an "All" tab.
- Each tab shows a badge with the item count for that category.
- Clicking a tab filters the table below to that category (URL parameter `?cat=<slug>`).

**Behaviour — Product Table**
- Columns: Image thumbnail, Name, Price, Category, Actions.
- Actions: Edit (links to `menu-edit.php?id=<id>`), Delete (GET request with `?delete=<id>`).
- Delete is immediate with no confirmation dialog.

**Access control**
- Requires an active admin session. Unauthenticated requests are redirected to `login.php`.

---

### 3.6 Feature: Admin Menu Item Edit

**Path:** `/cmsweb/admin/menu-edit.php?id=<id>`

**Description**
A form for editing a single menu item.

**Behaviour**
- Loads the existing product data by `id`.
- Pre-fills all form fields (name, price, category, current image preview).
- On submit: updates the `products` row. If a new image is uploaded, replaces the stored filename; otherwise keeps the existing image.
- Redirects back to `admin/menu.php` with a success message after saving.
- If the `id` parameter is missing or invalid, redirects to `admin/menu.php`.

**Access control**
- Requires an active admin session.

---

### 3.7 Feature: Admin About Page Editor

**Path:** `/cmsweb/admin/about.php`

**Description**
A form-based editor for all content on the public About Us page.

**Behaviour**
- Displays three collapsible sections, one per About page section.
- Each section has fields for: headline/title, body text (textarea), image upload, and image alt text.
- Current images are shown as previews. Selecting a new file shows a live preview before saving (via `FileReader` API).
- On submit: upserts all 13 key-value pairs into `about_content` using `INSERT ... ON DUPLICATE KEY UPDATE`.
- If a new image is uploaded for a section, the old file is not deleted from disk — only the DB reference is updated.
- Validates uploaded image MIME type against an allowlist (`image/jpeg`, `image/png`, `image/gif`, `image/webp`).

**Access control**
- Requires an active admin session.

---

### 3.8 Feature: Admin Logout

**Path:** `/cmsweb/admin/logout.php`

**Description**
Destroys the current admin session and redirects to the login page.

**Behaviour**
- Calls `session_unset()` and `session_destroy()`.
- Redirects to `admin/login.php`.
- Accessible via the "Logout" link in the admin sidebar.

---

## 4. System Design

### 4.1 Architecture Overview

```
Browser
  │
  ├── GET /cmsweb/public/about.php  ──► about.php ──► MySQL (about_content)
  ├── GET /cmsweb/public/menu.php   ──► menu.php  ──► MySQL (products)
  │
  └── /cmsweb/admin/
        ├── login.php    ──► Session auth ──► MySQL (admin_creds)
        ├── menu.php     ──► CRUD         ──► MySQL (products) + disk (assets/uploads/)
        ├── menu-edit.php──► CRUD         ──► MySQL (products) + disk (assets/uploads/)
        ├── about.php    ──► CRUD         ──► MySQL (about_content) + disk (assets/uploads/)
        └── logout.php   ──► Session destroy
```

There is no API layer. Every request is handled by a single PHP file that reads/writes the database and returns a full HTML page.

### 4.2 Tech Stack

| Layer | Technology | Notes |
|---|---|---|
| Web server | Apache 2.4 (via XAMPP) | `.htaccess` not used; direct file routing |
| Backend | PHP (procedural) | No framework, no Composer |
| Database | MySQL 8 (via XAMPP) | Database name: `cart_db` |
| DB extension | `mysqli` (procedural API) | Prepared statements used for auth queries |
| Frontend | HTML5 + CSS3 + vanilla JS | No build step, no bundler |
| Fonts | Avenir (self-hosted WOFF/TTF) | 4 weights: Roman, Medium, Heavy, Black |
| Icon font | icomoon (self-hosted) | Search, arrows, social icons |
| Admin icons | Font Awesome 5.15.4 (CDN) | Loaded from `cdnjs.cloudflare.com` |
| Dev environment | XAMPP on macOS | `localhost/cmsweb/` |
| Version control | Git | Hosted on GitHub |

### 4.3 Directory Structure

```
cmsweb/
├── config.php              # DB connection + global constants
├── index.php               # Redirects to public/about.php
│
├── public/
│   ├── about.php           # Public About Us page
│   └── menu.php            # Public Tea Menu page
│
├── admin/
│   ├── login.php           # Admin authentication
│   ├── logout.php          # Session destruction
│   ├── menu.php            # Menu item list + add + delete
│   ├── menu-edit.php       # Menu item edit
│   └── about.php           # About page content editor
│
├── includes/
│   ├── navbar.php          # Public site navigation bar
│   ├── footer.php          # Public site footer
│   ├── admin_header.php    # Admin sidebar + topbar
│   └── admin_footer.php    # Admin closing HTML
│
└── assets/
    ├── css/
    │   ├── style.css       # Public site styles (~1,230 lines)
    │   └── admin.css       # Admin panel styles (~865 lines)
    ├── fonts/              # Avenir + icomoon font files
    ├── images/             # Static brand photography + SVG logo
    └── uploads/            # User-uploaded product + about images
```

### 4.4 Data Model

#### `admin_creds`

Stores admin user credentials and brute-force lockout state.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | Row identifier |
| `username` | VARCHAR(100) | UNIQUE, NOT NULL | Admin login name |
| `password_hash` | VARCHAR(255) | NOT NULL | bcrypt hash of password |
| `failed_attempts` | INT | DEFAULT 0 | Consecutive failed login count |
| `last_failed_login` | DATETIME | NULLABLE | Timestamp of last failed attempt |
| `locked_until` | DATETIME | NULLABLE | Account locked until this time |

#### `products`

Stores all tea menu items.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | Row identifier |
| `name` | VARCHAR(255) | NOT NULL | Display name of the menu item |
| `price` | VARCHAR(50) | NOT NULL | Price as a string (e.g. `"$6.50"`) |
| `image` | VARCHAR(255) | NULLABLE | Filename only (e.g. `item_1234_abcd.png`) |
| `category` | VARCHAR(50) | DEFAULT `'milktea'` | Category slug (see PRD §3.3) |

#### `about_content`

Stores all editable content for the public About Us page as key-value pairs.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT | Row identifier |
| `field_key` | VARCHAR(100) | UNIQUE, NOT NULL | Content identifier |
| `field_value` | TEXT | NULLABLE | The content value |

**Known keys:**

| Key | Used for |
|---|---|
| `story_title` | "Our Story" section headline |
| `story_para1` | "Our Story" first paragraph |
| `story_para2` | "Our Story" second paragraph |
| `story_image` | "Our Story" image filename |
| `story_image_alt` | "Our Story" image alt text |
| `section2_title` | "Footprint in Numbers" headline |
| `section2_text` | "Footprint in Numbers" body text |
| `section2_image` | "Footprint in Numbers" image filename |
| `section2_image_alt` | "Footprint in Numbers" image alt text |
| `section3_title` | "Journey in Australia" headline |
| `section3_text` | "Journey in Australia" body text |
| `section3_image` | "Journey in Australia" image filename |
| `section3_image_alt` | "Journey in Australia" image alt text |

### 4.5 Request / Response Flow

#### Public page load

```
1. Browser sends GET /cmsweb/public/menu.php?cat=milktea
2. PHP reads $_GET['cat'], sanitises value
3. mysqli query: SELECT * FROM products WHERE category = 'milktea'
4. PHP loops over result rows, builds HTML product cards
5. Full HTML page returned to browser
```

#### Admin form submission (add menu item)

```
1. Browser sends POST /cmsweb/admin/menu.php
2. PHP checks $_SESSION['admin_logged_in'] — redirects if absent
3. PHP reads $_POST fields, validates not empty
4. If file uploaded: move_uploaded_file() → assets/uploads/<generated_name>
5. mysqli_real_escape_string() on text fields
6. INSERT INTO products (name, price, image, category) VALUES (...)
7. Set flash message in $_SESSION, redirect to same page (PRG pattern)
8. On next GET: display flash message, render updated product table
```

#### Admin login

```
1. Browser sends POST /cmsweb/admin/login.php
2. Prepared statement: SELECT * FROM admin_creds WHERE username = ?
3. Check locked_until — reject if in future
4. password_verify($input, $row['password_hash'])
5a. Success: $_SESSION['admin_logged_in'] = true, reset failed_attempts, redirect to menu.php
5b. Failure: increment failed_attempts; if ≥3 in 15 min → set locked_until, show error
```

### 4.6 Security Model

| Threat | Mitigation |
|---|---|
| Brute-force login | 3-attempt lockout per 15-minute window, 10-minute lockout |
| Username enumeration | Identical error message for unknown user and wrong password |
| SQL injection (auth) | Prepared statements with bound parameters |
| SQL injection (other) | `mysqli_real_escape_string()` on all user input before interpolation |
| XSS | `htmlspecialchars()` / `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')` on all output |
| Unauthorised admin access | Session guard at top of every admin page |
| Malicious file upload | MIME type allowlist on about image uploads; extension check on product images |
| Path traversal | Uploaded filenames are fully regenerated server-side; user-supplied filenames are never used |

**Known gaps (not yet addressed):**
- No CSRF tokens on admin forms
- No HTTPS enforcement (local dev only)
- DB credentials hardcoded in `config.php` (no `.env` or secrets management)
- `assets/uploads/` is publicly web-accessible with no access controls
- Product image upload validates extension only, not MIME type

### 4.7 Configuration

**`config.php`**

```php
define('BASE_PATH', __DIR__);           // Filesystem root of the project
define('ASSETS_URL', '/cmsweb/assets'); // Web URL prefix for assets

$conn = mysqli_connect('localhost', 'root', '', 'cart_db');
```

Both constants are used throughout the codebase:
- `BASE_PATH` — used with `file_get_contents`, `include`, and `move_uploaded_file` paths.
- `ASSETS_URL` — used when building `<img src="...">` and `<link href="...">` attributes.

---

## 5. CMS & CRUD Reference

### 5.1 Content Managed by the CMS

| Content Type | Storage | Admin Screen | Public Page |
|---|---|---|---|
| Tea menu items | `products` table | `admin/menu.php`, `admin/menu-edit.php` | `public/menu.php` |
| About page copy & images | `about_content` table (key-value) | `admin/about.php` | `public/about.php` |
| Admin credentials | `admin_creds` table | No UI — managed directly in DB | N/A |

### 5.2 Products CRUD

| Operation | Where | Method | Details |
|---|---|---|---|
| **Create** | `admin/menu.php` | POST | Form with name, price, category, optional image upload. Filename generated as `item_<time()>_<bin2hex(random_bytes(4))>.<ext>`. |
| **Read (list)** | `admin/menu.php` | GET | `SELECT * FROM products` with optional `WHERE category = ?` filter. |
| **Read (single)** | `admin/menu-edit.php?id=<id>` | GET | `SELECT * FROM products WHERE id = <int>`. |
| **Update** | `admin/menu-edit.php` | POST | Updates name, price, category. Replaces image only if a new file is uploaded. |
| **Delete** | `admin/menu.php?delete=<id>` | GET | `DELETE FROM products WHERE id = <int>`. Immediate, no confirmation. |

### 5.3 About Content CRUD

| Operation | Where | Method | Details |
|---|---|---|---|
| **Create / Update** | `admin/about.php` | POST | `INSERT INTO about_content (field_key, field_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)`. All 13 keys are upserted on every save. |
| **Read** | `admin/about.php`, `public/about.php` | GET | `SELECT field_key, field_value FROM about_content`. Result is converted to an associative array keyed by `field_key`. |
| **Delete** | Not implemented | — | Content is never deleted; fields are cleared by saving an empty value. |

### 5.4 Image Upload Flow

```
User selects file in form
        │
        ▼
$_FILES['image']['tmp_name'] exists?
        │
   Yes  ▼
Check file extension (products) or MIME type (about)
        │
   Pass ▼
Generate safe filename:
  Products: item_<time()>_<bin2hex(random_bytes(4))>.<ext>
  About:    <field_key>_<time()>.<ext>
        │
        ▼
move_uploaded_file(tmp, BASE_PATH . '/assets/uploads/' . $filename)
        │
        ▼
Save $filename (not full path) to database
```

### 5.5 Admin Panel Navigation

```
/admin/login.php
        │
        ▼ (authenticated)
/admin/menu.php  ◄──────────────────────────────────────┐
        │                                                │
        ├── [Add item form]  ──► POST ──► redirect back  │
        ├── [Delete link]    ──► GET  ──► redirect back  │
        └── [Edit link]  ──► /admin/menu-edit.php ───────┘
                                    │
                              POST ──► redirect to menu.php

/admin/about.php
        │
        └── [Save form]  ──► POST ──► redirect back

/admin/logout.php  ──► session_destroy() ──► /admin/login.php
```

### 5.6 Local Development Setup

**Prerequisites:** XAMPP (Apache + MySQL) on macOS or Windows.

```bash
# 1. Clone the repository into the XAMPP htdocs directory
git clone <repo-url> /Applications/XAMPP/xamppfiles/htdocs/cmsweb

# 2. Start Apache and MySQL in the XAMPP Control Panel

# 3. Create the database
#    Open http://localhost/phpmyadmin
#    Create a new database named: cart_db

# 4. Tables are created automatically on first page load
#    (CREATE TABLE IF NOT EXISTS in each admin page)

# 5. Seed the admin user
#    Run this SQL in phpMyAdmin (replace the hash with your own bcrypt hash):
INSERT INTO admin_creds (username, password_hash)
VALUES ('admin', '$2y$10$YourBcryptHashHere');

# 6. Open the site
open http://localhost/cmsweb/
```

**To generate a bcrypt hash for the admin password:**

```php
<?php echo password_hash('your_password_here', PASSWORD_DEFAULT); ?>
```

Run this as a temporary PHP file via `http://localhost/cmsweb/hash.php`, copy the output, insert it into the database, then delete the file.

---

*Last updated: March 2026*
