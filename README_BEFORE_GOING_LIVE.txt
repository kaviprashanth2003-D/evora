╔══════════════════════════════════════════════════════════════╗
║           EVORAA — PRE-LAUNCH CHECKLIST                     ║
║           Read this before uploading to your host           ║
╚══════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 1 — UPDATE config/db.php  (REQUIRED)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Open config/db.php and update these 2 lines with the details
your hosting provider (e.g. Hostinger) gives you in cPanel:

  define('DB_USER', 'your_db_username');   ← change this
  define('DB_PASS', 'your_db_password');   ← change this

DB_HOST is already set to 'localhost' which works on most hosts.
DB_NAME is 'evoraa_db' — create a database with this name in cPanel.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 2 — IMPORT THE DATABASE  (REQUIRED)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Use the file: database/evoraa_db_clean.sql

In cPanel → phpMyAdmin:
  1. Create a new database named: evoraa_db
  2. Select that database
  3. Click Import → choose evoraa_db_clean.sql → Go


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 3 — UPLOAD ALL FILES  (REQUIRED)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Upload ALL project files into the public_html folder on your host.
Make sure the uploads/ folder is included — it contains your
product images (prod-008, prod-009, prod-010).


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 4 — ADMIN LOGIN  (REQUIRED)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Your admin account:
  Email:    admin@evoraa.com
  Password: (the password you set — same as your local setup)

Admin panel URL: https://yourdomain.com/admin/login.php

⚠️  IMPORTANT: Log in and change your admin password immediately
    after the site goes live!


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STEP 5 — PRODUCT IMAGES NOTE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Products prod-001 to prod-007 use Unsplash URLs → no action needed.
Products prod-008, prod-009, prod-010 use local images from:
  uploads/products/prod-008/
  uploads/products/prod-009/
  uploads/products/prod-010/
These are included in this zip. Make sure they upload to the
same paths on your server.

