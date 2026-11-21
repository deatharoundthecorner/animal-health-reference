# Security policy

## Reporting a vulnerability

Please **do not open a public issue** for a security problem.

Use GitHub's private reporting instead: open the repository's **Security** tab and choose **Report a vulnerability**. Include what you found, how to reproduce it, and the version or commit you tested. You will get an answer as soon as a maintainer can look at it.

## What counts

In scope: the PHP code in this repository, the admin area, file uploads, the installer and the database schema (for example SQL injection, cross-site scripting, CSRF, authentication or authorization bypass, unsafe file handling).

Out of scope: problems that need a server you have misconfigured yourself (for example leaving `display_errors` on, running as MySQL `root`, or exposing the admin area without HTTPS), missing rate limiting on the login page (known limitation, see the README), and the accuracy of the sample data (report that as a data correction, using the issue template).

## Supported versions

Only the latest commit on `main` is supported.

## Hardening checklist for people who deploy this

See the **Security** section of the [README](README.md) and [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
