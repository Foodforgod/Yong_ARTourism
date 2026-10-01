# AR Tourism Explorer

A generic PHP/MySQL platform that connects tourism posters with destination information and image-tracked AR experiences. This workspace starts with the schema-first foundation and Phase 1; later phases are laid out below so the core can grow without mixing database, security, and AR concerns.

## Architecture

- `database/database.sql`: normalized MySQL schema and generic demo content.
- `includes/config.php`: application/session settings and XAMPP defaults.
- `includes/database.php`: shared PDO connection; optional `AR_DB_HOST`, `AR_DB_NAME`, `AR_DB_USER`, and `AR_DB_PASS` environment overrides.
- `includes/functions.php` and `includes/auth.php`: escaping, URL generation, CSRF, activity logging, and admin authorization.
- `admin/`: isolated admin entry points.
- `assets/`: public styles and (in later phases) uploaded media/AR targets.
- Public pages remain server-rendered PHP; Bootstrap and Font Awesome are presentation dependencies only.

## Database design

The schema centers on `categories`, `destinations`, and `attractions`; `attraction_images` holds gallery media. `ar_posters` owns an uploaded image and compiled MindAR target state. `ar_hotspots` links a poster to an attraction and stores normalized `x`, `y`, `width`, and `height` values so layouts scale with the poster. `users` stores password hashes and account status. `activity_logs` records administrator actions; `settings` provides a small key/value configuration store. Foreign keys define deletion behavior and indexes cover public listing and management queries.

## Folder structure

```text
Yong_ARTourism/
├── admin/                 # Login, dashboard, and future management screens
├── api/                   # Future JSON endpoints
├── ar/                    # Future MindAR/Three.js modules
├── assets/css/            # Public and admin styling
├── assets/images/         # Local site imagery
├── assets/uploads/        # Validated destination, attraction, and poster uploads
├── assets/ar-targets/     # Compiled .mind target files
├── database/database.sql  # Schema and demo records
├── includes/              # Configuration, PDO, auth, reusable page parts
├── index.php              # Public homepage
├── install.php            # One-time first-admin setup
└── README.md
```

## Implementation status
<img width="1900" height="1071" alt="image" src="https://github.com/user-attachments/assets/dba13fe8-7d81-4d06-b5b6-414ff14deee1" />

1. **Foundation (implemented):** schema, demo records, shared configuration/PDO, escaping and CSRF helpers, first-admin setup, login/logout, protected dashboard, responsive public homepage.
<img width="1915" height="1080" alt="image" src="https://github.com/user-attachments/assets/b9d3c248-e51d-4ba3-9768-1ea027dbdfe5" />

2. **Tourism content (implemented):** category, destination, and attraction CRUD with search, public listing/detail pages, maps, and validated poster image upload. Attraction gallery upload and list pagination remain follow-up work.
<img width="1917" height="1071" alt="image" src="https://github.com/user-attachments/assets/7a9450e7-c996-4efe-a49e-598a8c62ad35" />

3. **Poster management (implemented):** poster upload, visual normalized-coordinate hotspot editor, attraction assignment, save/delete/move/resize, and activity logging.
4. **AR targets and scanner (implemented, device test pending):** browser-side MindAR compilation produces a `.mind` buffer stored under the poster; the scanner uses MindAR Three.js image tracking, rear camera, hotspots, found/lost states, and attraction cards. A real target and supported mobile browser are required to verify tracking.
<img width="1902" height="1067" alt="image" src="https://github.com/user-attachments/assets/999f99b5-0a9b-4f7d-87a2-b3f83cf8a5b4" />

5. **Visitor integrations (implemented):** information cards, YouTube URL parsing with muted start and mute control, maps, and dynamically generated QR code. Social sharing is not included yet.
6. **Hardening and release (partial):** prepared statements, escaped output, CSRF, admin checks, login throttling, image MIME/dimension validation, non-executable upload rules, and HTTPS messaging are in place. Full device/browser/security testing, attraction media uploads, user/settings management, and cPanel deployment testing remain.

Browser camera access requires HTTPS, except for localhost. MindAR target compilation runs in the administrator's browser and recognition depends on real poster images and supported mobile browsers; neither camera tracking nor target quality can be verified without a real device/poster pair.

## Requirements

- XAMPP with Apache, MySQL/MariaDB, and PHP 8.1+ (tested locally with PHP 8.4).
- XAMPP with Apache, MySQL/MariaDB, and PHP 8.3+ (tested locally with PHP 8.4).
- A modern browser. Mobile camera use requires HTTPS or localhost.

## XAMPP installation

1. Place this directory in `C:\xampp\htdocs\Yong_ARTourism`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin, import `database/database.sql`, and confirm it creates the project-specific `yong_artourism` database. The existing `ar_tourism` database is not used or modified.
4. Check `includes/config.php` or set the `AR_DB_*` environment variables if your MySQL credentials differ.
5. Open `http://localhost/Yong_ARTourism/`.
6. Visit `http://localhost/Yong_ARTourism/install.php` and create the first admin account (password minimum: 12 characters). The setup page becomes read-only once an account exists.
7. Sign in at `/admin/login.php`.

On cPanel, create a MySQL database/user, import the SQL, set the four `AR_DB_*` environment values in the hosting configuration, upload the project under the document root, then enforce HTTPS before enabling camera-based scanning.

## Security notes

All database writes and user-supplied values use PDO prepared statements in the implemented authentication flows; rendered values are escaped. Admin mutations use CSRF tokens, sessions use HttpOnly/SameSite cookies, login attempts are throttled per session, and each protected request rechecks that the admin account is active. Before public deployment, configure HTTPS, restrict access to `install.php` after setup, and keep upload directories non-executable.

## Current status
The public site, content workflows, admin login, poster editor, browser-side target compilation, scanner, and QR are implemented. Locally, the schema and demo data are loaded in `yong_artourism`; the pre-existing `ar_tourism` database was left untouched. Mobile tracking still needs verification with a real compiled poster on HTTPS.
