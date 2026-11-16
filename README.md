# Digital Business Card | کارت ویزیت دیجیتال

A self-hosted digital business card built with PHP, HTML, CSS, and vanilla JavaScript. It includes a PHP admin panel, JSON-based content storage, Persian and English interfaces, and Progressive Web App (PWA) support.

[![فارسی | Persian](https://img.shields.io/badge/راهنما-فارسی-16895B?style=for-the-badge)](README.fa.md) [![English](https://img.shields.io/badge/README-English-2563EB?style=for-the-badge)](README.md)

<p align="center">
  <img src="docs/assets/mobile-card-preview.png" alt="Digital Business Card mobile preview" width="320">
</p>

## Download the project

**[Download the complete source package — v1.1.1](releases/digital-business-card-v1.1.1.zip)**

The ZIP contains the application source, setup documentation, demo media, and a first-login administrator credential. The package uses generic demo profile data rather than the original personal profile.

## Demo and preview

- [Mobile card walkthrough (WebM)](docs/assets/mobile-card-demo.webm)
- [Admin panel walkthrough (MP4)](docs/assets/admin-panel-demo.mp4)
- [Project media and preview notes](docs/PROJECT_MEDIA.md)

## Features

- **Profile management:** edit the name, professional title, biography, skills, statistics, and profile image.
- **Contact and social links:** add contact methods, social networks, and custom links.
- **Professional sections:** show work experience, education, certifications, projects, gallery, location, and resume information.
- **PHP admin panel:** manage card content from a browser-based dashboard.
- **Access protections:** password-based administrator access, CSRF checks, session handling, and login-attempt throttling.
- **Persian and English:** bilingual interface with Jalali/Gregorian date handling.
- **Personalization:** adjust the card theme and visual appearance.
- **PWA support:** install the card on supported devices and use the service-worker offline fallback.
- **Export and print:** save the card as PNG or print/export it to PDF using the browser.
- **No database required:** editable content is stored in JSON files.

## Technology

- PHP 8.1+
- HTML5 and CSS3
- Vanilla JavaScript
- JSON file storage
- Apache `.htaccess` configuration
- Progressive Web App APIs

## Run locally

1. Download and extract the [source package](releases/digital-business-card-v1.1.1.zip).
2. Make sure PHP 8.1 or newer is installed, with the `mbstring` and `gd` extensions enabled.
3. Open a terminal in the extracted project directory.
4. Start PHP's built-in development server:

   ```bash
   php -S 127.0.0.1:8080 -t .
   ```

5. Visit [http://127.0.0.1:8080](http://127.0.0.1:8080).

The administrator sign-in page is available at `/admin/login.php`.

## Deploy to a web host (Apache / cPanel)

1. **Choose a compatible host.** Use a hosting plan with PHP 8.1 or newer and Apache support. Enable the PHP `mbstring` and `gd` extensions. JSON support and PHP sessions must also be enabled.
2. **Create the target directory.** In cPanel File Manager, open `public_html` for the main domain, or create a subdirectory if the card will live under a path such as `example.com/card/`.
3. **Upload and extract the ZIP.** Upload `digital-business-card-v1.1.1.zip`, extract it in the target directory, and make sure the project files (including `index.html`, `admin/`, `data/`, `css/`, `js/`, and `image/`) are directly inside that directory. Do not leave the entire project nested one extra level unless that is the intended URL.
4. **Keep hidden files.** Make sure the upload method transfers every `.htaccess` file. In cPanel File Manager, enable **Show Hidden Files (dotfiles)**. These files contain important access restrictions and Apache rules.
5. **Set folder permissions.** The PHP process must be able to write to `admin/`, `data/`, `data/uploads/`, and `data/resume/`. Start with the host's normal ownership-based permissions (commonly directories `755` and files `644`); use `775` only if your host requires it and the directory is owned by the correct account. Do **not** use `777`. If saving the card or uploading media fails, ask the host which PHP user owns the files and adjust ownership/permissions accordingly.
6. **Enable HTTPS.** Install or activate an SSL certificate and open the site over `https://`. HTTPS is required for normal PWA installation and service-worker features on a public domain.
7. **Open the site and test it.** Visit your domain, open `/admin/login.php`, sign in with the default credentials shown below, and immediately change the password from the admin panel's **Account Security / امنیت حساب** section. Then test saving card data, uploading an image, and opening the card in a private browser window.

The project uses `.htaccess` rules and is intended for Apache hosting. If the host returns **500 Internal Server Error**, check the hosting error log and ask support whether `.htaccess` overrides and the required Apache modules (`mod_headers`, `mod_rewrite`, and `mod_expires`) are available. Do not remove the security rules as a first troubleshooting step.

**Subdirectory installation:** the project uses relative paths, so it can run in a subdirectory as long as the complete folder structure is preserved and the host applies the `.htaccess` files.

**Important:** GitHub Pages serves static files and cannot execute PHP. Use a PHP-capable web host for the working application. PHP's built-in server is for local testing, not production hosting.

## Default administrator login — change immediately

The downloadable ZIP includes these initial demo credentials:

| Field | Default value |
|---|---|
| Username | `admin` |
| Password | `admin` |

**Security warning:** these are public, shared demo credentials. Change the password immediately after the first login, before leaving the site accessible to other people. Do not keep `admin` as the password on a public production website. The panel requires at least 6 characters; use a unique password of 12 or more characters where possible.

To change it, sign in, open the admin panel's **Account Security / امنیت حساب** section, enter the current password, and save a new password.

## How password storage works

Passwords are **not stored as plain text** and are not encrypted in a reversible format. The application uses PHP's `password_hash()` with `PASSWORD_BCRYPT` to create a salted BCrypt hash. The credential file is `admin/auth_hash.php`; it stores the username and password hash, not the original password. During login, PHP's `password_verify()` checks the submitted password against the hash. When the password is changed, the file is rewritten with a new BCrypt hash. The application can also re-hash a credential when PHP indicates that the stored hash needs an update.

The downloadable ZIP contains a BCrypt hash for the initial `admin` password. The repository's normal Git working tree excludes `admin/auth_hash.php` through `.gitignore` so a site's live credentials are not accidentally committed. The `admin/.htaccess` file also blocks direct HTTP access to credential and secret files on Apache.

## Security and privacy

- The source package uses generic demo profile data, not the original profile data.
- The original admin hash was replaced with a fresh BCrypt hash for the documented demo login `admin/admin`.
- Runtime secrets, login-attempt data, personal uploads, and private resume files are not included.
- Fonts with unclear redistribution permissions were omitted.
- Static PHP/JavaScript syntax, JSON parsing, service-worker precache paths, and basic HTTP route checks passed in the preparation environment.
- This project has not undergone an independent security audit or a full production deployment test.

The supplied demo recordings visibly show the contact details configured on the sample card. Review the media before reusing it outside this repository.

## License

No `LICENSE` file is included, at the project owner's request. Public availability does not grant permission to reuse, modify, or redistribute the source code. All rights remain with the respective copyright holder(s).

## Version

**v1.1.1**
