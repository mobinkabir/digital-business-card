# Digital Business Card | کارت ویزیت دیجیتال

A self-hosted digital business card built with PHP, HTML, CSS, and vanilla JavaScript. It includes a PHP admin panel, JSON-based content storage, Persian and English interfaces, and Progressive Web App (PWA) support.

[فارسی بخوانید — Persian README](README.fa.md)

<p align="center">
  <img src="docs/assets/mobile-card-preview.png" alt="Digital Business Card mobile preview" width="320">
</p>

## Get the project

**[Download the complete sanitized source package — v1.1.0](releases/digital-business-card-v1.1.0.zip)**

The downloadable ZIP contains the application source and setup documentation. It is a public-ready copy of the supplied project: real profile data, administrator password hashes, secrets, private uploads, and fonts with unverified redistribution permissions are excluded.

## See it in action

- [Mobile card walkthrough (WebM)](docs/assets/mobile-card-demo.webm)
- [Admin panel walkthrough (MP4)](docs/assets/admin-panel-demo.mp4)
- [All project media and preview notes](docs/PROJECT_MEDIA.md)

## Features

- **Profile management:** edit the name, professional title, biography, skills, statistics, and profile image.
- **Contact and social links:** add contact methods, social networks, and custom links.
- **Professional profile sections:** present work experience, education, certifications, projects, gallery, location, and resume information.
- **PHP admin panel:** manage card content from a browser-based dashboard.
- **Access protections:** password-based administrator access, CSRF checks, session handling, and login-attempt throttling.
- **Persian and English:** bilingual interface with Jalali/Gregorian date handling.
- **Personalization:** adjust the card's theme and visual appearance.
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

1. Download and extract the [source package](releases/digital-business-card-v1.1.0.zip).
2. Make sure PHP 8.1 or newer is installed.
3. Open a terminal in the extracted project directory.
4. Start PHP's built-in development server:

   ```bash
   php -S 127.0.0.1:8080 -t .
   ```

5. Visit [http://127.0.0.1:8080](http://127.0.0.1:8080).

The administrator sign-in page is available at `/admin/login.php`. The public package does not include a pre-created administrator account or password hash. Follow the included setup guide to create the first administrator credentials on your own server.

## Requirements and deployment

- PHP 8.1 or newer
- PHP `mbstring` and `gd` extensions for image-processing workflows
- PHP JSON support and sessions enabled
- Apache is recommended for hosting because the project includes `.htaccess` rules

**Important:** GitHub Pages serves static content and cannot execute PHP. To run the complete application, deploy it to a PHP-capable web host.

## Security and privacy

- The public source package uses generic demo profile data.
- Original administrator password hashes, secrets, personal uploads, and private resume files are not included.
- The original package's default `admin/admin` credentials were removed.
- Fonts with unclear redistribution permissions were omitted.
- Static PHP/JavaScript syntax, JSON parsing, service-worker precache paths, and basic HTTP route checks passed in the preparation environment.
- This project has not undergone an independent security audit or a full production deployment test.

The supplied demo recordings visibly show the contact details configured on the sample card. Review the media before reusing it outside this repository.

## License

No `LICENSE` file is included, at the project owner's request. Public availability does not grant permission to reuse, modify, or redistribute the source code. All rights remain with the respective copyright holder(s).

## Version

**v1.1.0**
