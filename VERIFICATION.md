# Verification record

Date: 2026-11-12 (Asia/Bangkok). This is a local demonstration, not a production audit.

## Redesign — theme 1.1.0

- All four pages were restyled from the supplied visual reference: cream background, vivid purple, yellow and green, thin typography, highlighted words, tilted posters and rounded sections. The actual reference image is not redistributed.
- Chrome DevTools was used for initial visual inspection. The embedded browser completed responsive and interaction checks after the DevTools screenshot connection became unresponsive.
- Twenty browser layout checks passed: Home, About, Research and Contact at 320, 390, 768, 1024 and 1440 pixels. Every layout had one H1, loaded images and no horizontal overflow.
- Mobile menu open/close and Escape behavior passed. Research category counts, pressed states, expandable detail and FAQ behavior passed.
- The redesigned contact form rejected an empty submission and successfully stored a sample enquiry. A separate database check confirmed that the sample was stored as a private enquiry. No email was sent.
- Main title text matches the stored page title fields on all four pages; visual highlights do not change saved content. PHP and JavaScript syntax checks passed after the redesign.
- Four page responses and their referenced frontend assets returned HTTP 200, with no PHP warnings or remote assets. The browser's captured error/warning log was empty.
- New desktop screenshots for all four pages, a mobile screenshot and the WordPress theme thumbnail are included.
- New clean source, full-XAMPP and theme-only archives are supplied. The theme-only archive supports updating an existing installation without replacing its database or configuration. Core, contact processing and demo seeding behavior remain unchanged.

The records below describe the original installation and backend verification, which this visual update preserves.

## Environment

- WordPress 7.1.4 from the official pinned archive.
- XAMPP PHP 8.1.25 and MariaDB 10.4.32 on Windows.
- Isolated database directory and port, separate from existing projects.
- PHP built-in HTTP server; Chrome DevTools for browser verification.
- The XAMPP Apache executable was also attempted with an isolated configuration, but rejected the task runner's filesystem paths before serving requests. Apache/XAMPP Control Panel installation is therefore **not directly verified** here. No system Apache configuration was changed. The documented htdocs workflow uses standard WordPress/PHP files and plain permalinks.

## Passed

- PHP syntax checks on all 10 custom PHP files; JavaScript syntax check.
- Fresh installer: user-chosen admin password, new database, unique configuration, theme activation, 4 pages, correct Home and plain permalinks.
- Installer locks after configuration is written.
- All four pages return HTTP 200, contain one H1 and show no PHP warnings.
- Chrome: all four pages at 320px viewport have no horizontal overflow, broken images or external asset requests. Home and Research also checked at 390px; desktop layouts visually inspected at 1440px.
- Mobile navigation opens, reports aria-expanded and closes on Escape.
- Research filters show 1 precision-science area or 2 disease-biology areas; active states update correctly. Research details and FAQ expand.
- Contact form rejects empty required fields in the browser and submits a valid sample through normal browser navigation.
- Server rejects forged nonce, invalid email, missing consent, filled honeypot and messages over 3,000 characters.
- Valid enquiries persist; repeated submission inside one minute is throttled.
- Anonymous users are sent to login when opening the inbox; no public enquiry REST collection is registered.
- Administrator login, enquiry list and stored sanitized message details work.
- Editing the introductory paragraph and hero title through the native WordPress edit form changes the frontend, including the intended heading line break. Original sample copy restored after testing.
- 25 HTTP/admin integration assertions passed. Console and network checks on inspected frontend flows found no errors or failed asset requests.
- Six additional checks passed: reseeding keeps page IDs, preserves edited copy, keeps exactly four published demo pages, stores no public enquiries, keeps the inbox non-publicly-queryable and denies the inbox to an Editor account.

## Packaging checks

- The included PowerShell packaging script completed successfully using the pinned archive.
- The generated XAMPP ZIP passed CRC validation and standard extraction. It included core, installer, theme, mu-plugin, local images, preview and .gitignore.
- No wp-config.php, installation lock, SQL dump, QA credentials or runtime database was present in the clean package.
- A second fresh installation under `/aurevia-demo/` succeeded using the same release source. Assets and navigation resolved under that subfolder, and seeded heading line breaks rendered correctly.
- The installer refused a nonempty database before creating wp-config.php; selecting a new database then succeeded.
- Final source and full release archives are checked against source bytes; runtime configuration and QA data are excluded. SHA256SUMS.txt accompanies the ZIP files.

## Limits

No real email delivery, public hosting, domain/email setup, performance benchmark, formal WCAG audit, penetration test, clinical claim verification or regulatory compliance assessment was performed. Sample enquiries exist only in the isolated QA installation and are excluded from distributed archives. No credentials, configured wp-config.php or SQL dump belong in either release ZIP.
