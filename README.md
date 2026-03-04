# cmsweb — Gong Cha Australia CMS

A PHP/MySQL content management system for the Gong Cha Australia website.

## Project Structure

```
cmsweb/
├── index.php               # Entry point — redirects to public/about.php
├── config.php              # Database connection + BASE_PATH / ASSETS_URL constants
│
├── public/                 # Public-facing pages
│   ├── about.php           # About Us page
│   └── menu.php            # Tea Menu page
│
├── admin/                  # Admin panel (session-protected)
│   ├── login.php           # Login form with brute-force protection
│   ├── logout.php          # Session destroy + redirect
│   ├── menu.php            # Manage menu items (add / delete)
│   ├── menu-edit.php       # Edit a single menu item
│   └── about.php           # Edit About page content + images
│
├── includes/               # Shared PHP partials
│   ├── navbar.php          # Public site navigation bar
│   ├── footer.php          # Public site footer
│   ├── admin_header.php    # Admin panel HTML head, sidebar, topbar
│   └── admin_footer.php    # Admin panel closing tags
│
└── assets/                 # All static files
    ├── css/
    │   ├── style.css       # Public site styles
    │   └── admin.css       # Admin panel styles
    ├── fonts/              # Avenir typeface + icomoon icon font
    ├── images/             # Static brand images (logo, photography)
    └── uploads/            # User-uploaded product and about-page images
```

## Database

MySQL database: `cart_db`

| Table           | Purpose                                              |
|-----------------|------------------------------------------------------|
| `admin_creds`   | Admin accounts with bcrypt passwords + lockout logic |
| `products`      | Menu items (name, price, image, category)            |
| `about_content` | Key-value store for About page text and images       |

## Getting Started

1. Start Apache and MySQL in XAMPP.
2. Import the database or let the app auto-create tables on first run.
3. Visit `http://localhost/cmsweb/` — redirects to the About page.
4. Admin panel: `http://localhost/cmsweb/admin/login.php`
