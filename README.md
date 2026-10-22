# SharePlate — Surplus Food Sharing Platform
# SharePlate — Surplus Food Sharing Platform

A local-first, responsive food-sharing project built with HTML, CSS, JavaScript, core PHP, and MySQL. Donors can publish surplus food, recipients and charities can request collection, donors review requests and record completed pickups, and the database automatically expires listings after their deadline.

## Requirements

- XAMPP with Apache, MySQL/MariaDB, and PHP 8.0+ (PDO MySQL and Fileinfo enabled)
- A modern browser
- Internet access only for the optional Google Fonts and Chart.js CDN resources; the rest works locally

## Install with XAMPP

1. Copy this project folder into `C:\xampp\htdocs\SharePlate` (or point Apache's document root at this folder).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, create/import the database by importing `database.sql` (the script creates `shareplate_db`).
4. Check the connection values in `config/database.php`. The defaults are host `127.0.0.1`, database `shareplate_db`, user `root`, and an empty password, matching a typical local XAMPP installation. Override with environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` where available.
5. Ensure `uploads/food` is writable by Apache. Uploaded JPG, PNG, and WebP images are limited to 4 MB; PHP's `upload_max_filesize` and `post_max_size` should allow at least 4 MB.
6. Browse to `http://localhost/SharePlate/` and register a donor and a recipient/charity account.
7. To create the first administrator securely, register a normal account, then from a terminal run `php C:\xampp\htdocs\SharePlate\make-admin.php your-email@example.com`. The CLI-only script updates that existing user's role; delete `make-admin.php` after use. Never create an admin through public registration.
8. Optionally schedule `php C:\xampp\htdocs\SharePlate\cron\expire-food.php` in Windows Task Scheduler. Expiry is also checked and written to the database each time listings, details, donor dashboard, and admin overview are loaded.

## Pages and features

- `index.php` — platform intro, live counters, searchable sample of current listings, and how-it-works section.
- `register.php`, `login.php`, `logout.php`, `profile.php` — role-based accounts and session management.
- `available-food.php` — active food search, locality search, category filter, sort, and expiry countdown.
- `add-food.php` — validated donor listing form and safe image upload.
- `food-details.php` — listing details and recipient/charity collection request form.
- `my-donations.php` — donor listings, incoming requests, accept/reject, cancel, and mark collected.
- `my-requests.php` — recipient/charity request status overview.
- `report.php` — report an inappropriate or unsafe listing.
- `admin/dashboard.php` — metrics, recent activity, and donation chart.
- `admin/users.php` — deactivate/reactivate non-admin accounts.
- `admin/donations.php` — filter listings, moderate inappropriate listings, and review reports.
- `admin/requests.php` — inspect and filter collection requests.
- `cron/expire-food.php` — optional expiry maintenance task.

## Request and quantity workflow

1. A recipient or charity creates one request per listing. Duplicate requests are blocked by a database unique constraint.
2. The donor accepts or rejects a pending request. Acceptance is checked against current remaining quantity and expiry.
3. When pickup is completed, the donor marks that accepted request **Collected**. In one transaction the app writes a collection record, updates the request, and deducts the quantity. Remaining food is labeled Partially Collected; zero remaining is Fully Collected; accepting a request reserves that quantity.
4. Expired listings stop accepting requests. Pending/accepted requests for expired listings cannot be accepted/completed.

The approval transaction accounts for quantities already reserved by other accepted requests. When an accepted request is marked collected, the reservation is converted into a deduction from remaining quantity. Rejected requests release their reservation.

## Sample data

For realistic local demo data, register a donor account, add a few listings through the form, then register a recipient/charity account and request them. This keeps every sample tied to real user IDs and avoids unsafe hard-coded credentials or invalid foreign keys. Use a future expiry time while testing.

## Functional checks

| Test | Expected result |
|---|---|
| Register a donor/recipient/charity | Account saved; duplicate email rejected; public admin role unavailable. |
| Sign in with incorrect credentials | Generic “Email or password is incorrect” message. |
| Donor submits valid listing | Listing appears on home and available-food pages. |
| Submit expired deadline or invalid image | Server rejects submission with a clear message; no listing is inserted. |
| Search by food/locality and category | Only matching active listings appear; sort changes listing order. |
| Recipient requests available quantity | Request appears in donor dashboard and recipient's My Requests. |
| Same recipient requests same listing again | Unique constraint rejects duplicate with a friendly message. |
| Donor accepts a request then marks collected | Collection record written; remaining quantity and donation status updated. |
| Request exceeds remaining quantity or listing is expired | Request/approval/completion is blocked server-side. |
| Recipient tries donor-only or admin route | Access is denied or redirected to sign-in. |
| User modifies a donation action for another donor | Ownership check prevents the change. |
| Admin reviews metrics, listings, reports | Counts and filtered administrative data come from MySQL. |

## Security notes

- All database queries use PDO prepared statements; output is escaped with `htmlspecialchars` through `e()`.
- Important POST operations use session CSRF tokens. Login regenerates the session ID, roles are checked server-side, and image names are random with MIME/size validation.
- No initial administrator credentials are shipped. Grant an admin role locally with the CLI script and remove it afterwards.
- For public deployment, configure HTTPS, secure session cookie settings, application-specific least-privilege database credentials, request rate limiting, backups, and a robust food-safety/report review policy. Do not expose donor contact numbers publicly; pickup contact details are coordinated after acceptance through the donor.

## Troubleshooting

- **Database connection failed:** verify MySQL is running and credentials/database name in `config/database.php`; import `database.sql` first.
- **Unknown database/table:** import the entire SQL script in phpMyAdmin and confirm `shareplate_db` is selected.
- **Could not upload image:** grant write access to `uploads/food`, enable PHP Fileinfo, and verify PHP upload limits.
- **Blank page / PHP error:** check Apache's PHP error log in XAMPP; enable display errors only for local development, not deployment.
- **PDO driver missing:** enable `pdo_mysql` in `php.ini` and restart Apache.
- **Chart unavailable:** dashboard metrics still work; Chart.js is loaded from CDN, so check network access or use the page without the chart.
- **Expired item still visible in an old browser tab:** reload the page; server/database status is authoritative. The JavaScript countdown is only a visual aid.
