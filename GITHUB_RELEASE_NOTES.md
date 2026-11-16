# Digital Business Card v1.1.1

## What's included
- Clickable Persian and English README language badges.
- Expanded shared-hosting deployment guide for Apache/cPanel.
- First-login administrator credentials (`admin` / `admin`) in the downloadable ZIP. Change the password immediately after the first login.
- Password storage documented as salted BCrypt generated with PHP `password_hash()` and checked with `password_verify()`.

## Security notes
- The downloadable ZIP uses generic demo profile data.
- The default administrator credential is public and must be changed before the website is exposed to other users.
- The downloadable ZIP includes only the BCrypt hash for that known demo password; it does not contain a plain-text password in the credential file.
- Runtime secrets, personal uploads, private documents, and login-attempt data are excluded.
- The live credential file remains ignored by Git in the normal repository working tree.

## Requirements
- PHP 8.1+
- `mbstring` and `gd` extensions
- Apache recommended; HTTPS required for public PWA/service-worker features

## Validation
Static PHP/JavaScript syntax, JSON parsing, service-worker precache paths, and basic HTTP route checks were previously performed in the preparation environment. Full production deployment testing and an independent security audit have not been completed.
