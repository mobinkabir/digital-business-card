# Digital Business Card v1.1.0

## Public source package

This release package is a sanitized distribution of the supplied project. It uses generic profile data and excludes the original admin password hash, runtime login-attempt data, personal user uploads, and fonts whose redistribution permissions were not verifiable from the supplied archive.

## Highlights

- PHP admin panel with JSON-backed content management
- Persian-first card with English support
- PWA manifest and service worker
- Profile, contact, social, experience, education, certification, projects, and resume sections
- Mobile preview and user-supplied walkthrough recordings are included in the repository documentation

## Requirements

- PHP 8.1+
- `mbstring` and `gd` recommended for image workflows
- Apache recommended for production hosting

## Validation

Static PHP syntax, JavaScript syntax, JSON parsing, service-worker precache paths, and basic HTTP route checks were performed in the preparation environment. A full production deployment test and independent security audit have not been completed.
