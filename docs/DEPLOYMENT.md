# Deploying Lingayen CivicLink

Step by step, from a tested local copy to a live site. Read §1 before choosing a host.

---

## 1. Before anything: PHP2Shell and the server you choose

In October 2026 two critical PHP flaws (**PHP2Shell**, CVE-2026-109618 and CVE-2026-109619) were
disclosed. They let anyone run code on a server without logging in, whatever the framework, and
mass scanning started the same day. They are fixed only by updating PHP:

| PHP branch | Safe from |
|---|---|
| 8.2 | 8.2.35 |
| 8.3 | 8.3.36 |
| 8.4 | 8.4.27 |
| 8.5 | 8.5.12 |

PHP 8.1 and older get no fix. **Do not put the site on a server whose PHP is older than the table.**
Ask the host before paying, or check the PHP version in its control panel. After setup, the
**System check** page (§5) confirms it.

**Which host**

- **For the defense demo (PRD §13):** a free or cheap shared PHP/MySQL host is enough, if its PHP
  is patched and it offers free HTTPS. Use made-up or test data only.
- **For real use with real CSO data:** member names, contact numbers and uploaded documents are
  personal data under the Data Privacy Act (RA 10173). Use LGU-approved hosting, or at least a paid
  host or VPS with SSH, patched PHP, HTTPS and backups.

**Server requirements:** PHP (patched, see above) with pdo_mysql, mbstring, openssl, fileinfo, gd,
zip, xml, curl and bcmath; MySQL 8 or MariaDB 10.4+; HTTPS; Apache with mod_rewrite (or Nginx).

---

## 2. Test locally first

1. `APP_URL=http://localhost php artisan test`: everything passes except
   `test_a_disguised_file_is_rejected_even_with_a_pdf_name` (fails on main already; it is about
   local file-type detection).
2. `composer audit` and `npm audit --omit=dev`: no advisories.
3. Walk through the main flow in a browser on a fresh copy (`php artisan migrate:fresh --seed`):
   - CSO: register, file an application with documents, log an activity with a sign-in sheet,
     edit it, withdraw one.
   - Admin: move the application through the SB readings and approve it; verify one activity,
     reject one (and try Undo); write and publish a post; register an organization with a login;
     reset a representative's password; download both Excel files; merge and delete a test
     organization.
   - Public site: directory, organization page, About, Resources, Contact form.
   - Phone width (360 px): sign-in, dashboard, one dialog.
4. Email: send yourself an invitation (Accounts › Register an organization, with a login) and a
   password reset, and click both links.

---

## 3. Prepare the files (on your computer)

```bash
composer install --no-dev --optimize-autoloader   # production dependencies only
npm ci && npm run build                           # creates public/build
php artisan key:generate --show                   # copy the output: the live APP_KEY
```

Copy `.env.production.example` to a file **outside the project** (for example `live.env` on your
desktop) and fill it in: the new APP_KEY, the https address, the database details from the host,
and a **new** Brevo SMTP key. Never reuse the development `.env`.

When the upload is done, run `composer install` again to get the development tools back.

Do **not** run `php artisan config:cache` on your computer for the server: it saves your local
paths into the cache. Only run it on the server (§4B).

---

## 4H. Hostinger (shared Premium or Business plan) with a Namecheap domain

Hostinger's shared plans include SSH, Composer, Git and a PHP version switch, and Hostinger
finished installing the PHP2Shell fix on its shared servers on 22 October 2026. (A Hostinger VPS
is different: there you patch PHP yourself.)

**1. Connect the domain (Namecheap → Hostinger)**
1. In Hostinger's hPanel, add the website with your Namecheap domain (Websites › Add website ›
   "use an existing domain"). hPanel shows the two nameservers to use **for your site**; copy them
   from there (they are often `ns1.dns-parking.com` / `ns2.dns-parking.com`, but use what hPanel shows).
2. In Namecheap: Domain List › Manage › Nameservers › **Custom DNS**, paste both, click the green ✓.
3. Wait for DNS (minutes to 48 hours). Then in hPanel turn on the free SSL certificate and
   "Force HTTPS".

**2. Server settings in hPanel**
- PHP Configuration: PHP **8.3 or 8.4**; extensions pdo_mysql, mbstring, fileinfo, gd, zip, intl on.
- Databases › MySQL: create a database and user with a long random password. Write them down.
- Advanced › SSH Access: enable it and note the command (`ssh -p 65002 uXXXXXXX@your-server-ip`).

**3. Put the code outside the web root, and make `public/` the web root**

```bash
ssh -p 65002 uXXXXXXX@your-server-ip
cd ~/domains/your-domain.me
git clone https://github.com/codew-kicha/Lingayen-CivicLink.git app
cd app
# Hostinger's PHP blocks proc_open, which Composer's post-install script needs: skip the scripts
# and run Laravel's one step (package discovery) directly.
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover --ansi
cp .env.production.example .env && nano .env      # APP_URL=https://your-domain.me, database, mail
php artisan key:generate
php artisan migrate --force
php artisan app:create-admin
php artisan storage:link   # if symlink() is disabled: ln -s "$PWD/storage/app/public" "$PWD/public/storage"
php artisan config:cache && php artisan route:cache && php artisan view:cache
# Serve only public/: replace the empty public_html with a link to it.
cd .. && mv public_html public_html.bak && ln -s app/public public_html
```

Build the styles on your computer (`npm ci && npm run build`) and upload `public/build/` to
`~/domains/your-domain.me/app/public/build/` with hPanel's File Manager or `scp`.

If you can't use SSH, follow §4A instead (upload the whole project into `public_html`; the
project's root `.htaccess` keeps everything except `public/` unreachable).

**4. Updating later**

```bash
cd ~/domains/your-domain.me/app && git pull
composer install --no-dev --optimize-autoloader --no-scripts && php artisan package:discover --ansi
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Then continue with §5.

## 4A. Shared hosting without SSH (cPanel, InfinityFree and similar)

1. **Database:** create a MySQL database and user in the control panel. Note the host, name, user
   and password into `live.env`.
2. **Tables and the first admin:** with no SSH you can't run migrations on the server, so build
   the database on your computer and import it:
   ```bash
   # an empty local database named lingayen_live, created in phpMyAdmin first
   DB_DATABASE=lingayen_live php artisan migrate --force
   DB_DATABASE=lingayen_live php artisan app:create-admin
   ```
   Export `lingayen_live` from phpMyAdmin (SQL) and import it into the host's phpMyAdmin.
   **Never run `--seed`**: the demo password is public on GitHub. The seeder refuses on a
   production setup anyway.
3. **Upload** the whole project into the web root (`htdocs` or `public_html`), **except**
   `node_modules/`, `.git/`, `tests/`, your local `.env`, and the contents of `storage/logs/`.
   Include `vendor/` and `public/build/`. Then upload `live.env` and rename it to `.env`.
   The project's root `.htaccess` sends every request into `public/` and keeps `.env`, `vendor`,
   logs and `.git` unreachable.
4. **Logos without storage:link:** set `PUBLIC_UPLOADS_IN_WEB_ROOT=true` in `.env` and create an
   empty folder `public/storage`.
5. **Permissions:** `storage/` (and everything in it) and `bootstrap/cache/` must be writable
   (755 or 775, depending on the host).
6. Continue with §5.

## 4B. VPS or hosting with SSH

```bash
git clone https://github.com/codew-kicha/Lingayen-CivicLink.git && cd Lingayen-CivicLink
composer install --no-dev --optimize-autoloader
cp .env.production.example .env        # then edit: key, URL, database, mail
php artisan migrate --force
php artisan app:create-admin
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Upload `public/build` from your computer (or install Node on the server and run `npm ci && npm run build`).

- **Web root** must be the `public/` folder (Apache `DocumentRoot …/public`, Nginx `root …/public`).
- **HTTPS:** Let's Encrypt (`certbot`), then keep `SESSION_SECURE_COOKIE=true`.
- **PHP:** install the patched version from the distribution or a maintained PPA; enable automatic
  security updates.
- **Firewall:** allow only SSH, 80 and 443. MySQL must listen on 127.0.0.1 only.
- After every code update: `git pull`, `composer install --no-dev -o`, `php artisan migrate --force`,
  then the three cache commands again.

---

## 4D. Demo data for the defense (optional)

The live site can carry the same demo data as your computer (organizations in every state,
activity histories, scores) for the panel to try, as long as the accounts don't use the
development password, which is public in the repository.

1. In the server's `.env`:
   ```
   ALLOW_DEMO_DATA=true
   DEMO_PASSWORD=your-private-password   # at least 12 characters, shared only with the panel
   ```
2. Load it: `php artisan config:clear && php artisan migrate:fresh --seed --force`, then the three
   cache commands from §4H. Every demo account (admin@lingayen.gov.ph, rep1@example.ph, …) now
   signs in with `DEMO_PASSWORD`. The seeder refuses if the password is missing, short, or the
   public one.
3. System check then passes **Demo accounts** and shows a **Demo data** warning, because the fake
   organizations appear in the public directory.

**After the defense, before real use:**
```bash
php artisan migrate:fresh --force        # wipes everything; no --seed
php artisan app:create-admin             # the office's real administrator
```
Set `ALLOW_DEMO_DATA=false`, delete the `DEMO_PASSWORD` line, run the three cache commands, and
re-open System check: the Demo data warning is gone.

## 5. Check it, then go live

1. Open `https://your-domain/.env` in a browser. It must show an error page, never settings.
2. Sign in with the admin you created and open **System check** (sidebar, System). Every item must
   say **Pass**. With SSH, `php artisan app:go-live-check` runs the same checks.
3. Send yourself an invitation and a password reset from the live site.
4. Register a real organization, upload a logo, and check that it shows on the public directory.

---

## 6. After going live

- **Backups, weekly:** export the database (phpMyAdmin or `mysqldump`) and download
  `storage/app` (uploaded documents and sign-in sheets). Keep a copy off the server.
- **Updates, monthly:** watch for PHP security releases and update; run `composer audit` locally
  and deploy dependency fixes; re-open System check after any server change.
- **If anything was ever exposed** (for example `.env` downloadable), replace the APP_KEY, the
  database password and the Brevo SMTP key at once.
- **Logs:** `storage/logs/` (daily files). Look for repeated errors or memory-limit fatal errors,
  which were the one server-side trace of PHP2Shell attempts.

---

## 7. Your development computer

XAMPP's Apache and MySQL listen on every network interface, and the Windows firewall allows Apache
on public networks. XAMPP's PHP 8.2.12 is affected by PHP2Shell.

- Update XAMPP once a release with a patched PHP is out, or stop Apache when you don't need it
  (`php artisan serve` is enough for this project and only listens on 127.0.0.1).
- On shared Wi-Fi, turn off the "Apache HTTP Server" rule for Public networks in Windows Defender
  Firewall.
- Set a password for MySQL's root user (phpMyAdmin › User accounts) and put it in `.env`.
