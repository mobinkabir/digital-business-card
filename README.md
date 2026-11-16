# Digital Business Card

A self-hosted, Persian-first digital business card template with a PHP admin panel, JSON-based content storage, and Progressive Web App (PWA) support.

## Features

- Editable profile, biography, skills, contact methods, social links, and custom links.
- Work experience, education, certifications, projects, gallery, location, and resume sections.
- PHP admin panel with password-protected access, CSRF checks, secure sessions, and login-attempt throttling.
- Persian-first interface with English support and Jalali/Gregorian date handling.
- Theme and card appearance customization.
- Installable PWA with service-worker caching and offline fallback.
- Save as PNG and print or export to PDF from the browser.
- No database required; editable content is stored in JSON files.

## Technology

- PHP 8.1+
- HTML5, CSS3, vanilla JavaScript
- JSON file storage
- Apache `.htaccess` rules
- Progressive Web App APIs

## Repository status

The public repository has been created and its project overview is in place. The complete, sanitized v1.1.0 source tree is prepared in the companion ZIP package. To publish it, extract the package and run `publish-to-github.ps1` from the project folder on Windows. The script clones this repository, copies the prepared files, displays the changes, asks for the exact confirmation `PUBLISH`, and pushes the source commit.

## Requirements

- PHP 8.1 or newer
- `mbstring` and `gd` PHP extensions for image-processing workflows
- JSON and PHP sessions enabled
- Apache recommended for production hosting

> GitHub Pages cannot execute PHP. Use a PHP-capable host to deploy the complete application.

## Security and privacy

- The prepared source package uses sanitized demo content, not the original profile data.
- Admin credentials, secrets, user uploads, and private resume documents are excluded.
- The original package's default `admin/admin` credential was removed.
- Fonts with unclear redistribution terms were omitted.
- Static syntax and JSON checks passed; the project has not undergone an independent security audit.

## License

No `LICENSE` file is included by the project owner's choice. Public visibility does not grant permission to reuse, redistribute, or modify the source; standard copyright restrictions apply.

## Version

`1.1.0`
