# Digital Business Card

A self-hosted, Persian-first digital business card template with a PHP admin panel, JSON-based content storage, and Progressive Web App (PWA) support.

## Features

- Editable profile, biography, skills, contact methods, social links, and custom links.
- Work experience, education, certifications, projects, gallery, location, and resume sections.
- PHP admin panel with password-protected access, CSRF checks, secure sessions, and login-attempt throttling.
- Persian-first interface with English support and Jalali/Gregorian date handling.
- Theme and card appearance customization.
- Installable PWA with service-worker caching and offline fallback.
- Save as PNG and print or export to PDF through the browser.

## Publication status

The public repository has been created. The full sanitized source package, downloadable ZIP, mobile preview, and demo recordings are prepared in the accompanying publishing kit but have not yet been pushed to this repository.

The original archive is not redistributed as-is because it contains a real admin password hash and personal profile/contact data. The public ZIP is a sanitized derivative with generic demo content.

## Technology

- PHP 8.1+
- HTML5, CSS3, vanilla JavaScript
- JSON file storage
- Progressive Web App APIs

## Requirements

- PHP 8.1 or newer
- `mbstring` and `gd` PHP extensions for image-processing workflows
- JSON and PHP sessions enabled
- Apache recommended for production hosting

> GitHub Pages cannot execute PHP. Use a PHP-capable host to deploy the complete application.

## License

No `LICENSE` file is included by the project owner's choice. Public visibility does not grant permission to reuse, redistribute, or modify the source; standard copyright restrictions apply.

## Version

`1.1.0`
