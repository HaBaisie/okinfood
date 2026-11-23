# Okin Food Supermarket - online store

Plain PHP 8.1+ and SQLite. Runs on any cPanel host. Not for Netlify (needs PHP).

## Deploy
1. Upload these files to the domain's document root.
2. Copy `config.sample.php` to `config.php`, then set the admin password, SMTP password, phone and bank details.
3. Make `data/` and `uploads/` writable (755). The database is created on first visit.
4. Admin panel: `/admin/`
