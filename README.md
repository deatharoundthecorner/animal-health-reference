# Animal Health & Anatomy Reference

An open-source reference for **biologists, veterinarians and students**. It links **animals** to their **body parts** and to the **diseases** that affect them, so you can move in any direction:

- from a species → its organs → the diseases that affect those organs
- from an organ → the species that have it and how it differs between them
- from a disease → affected species, organs, signs, transmission and prevention
- from a set of clinical signs → the recorded diseases associated with them

Every record cites its sources and shows whether a qualified person has reviewed it.

> **Reference only.** This is not a diagnostic or treatment tool. It deliberately contains no dosage or treatment advice.
>
> **The sample data has not been expert-reviewed.** It was drafted from general veterinary knowledge, linked to references, and is marked *Not yet expert-reviewed* everywhere it is shown. See [Data and trust](#data-and-trust).

## Features

**Public site**

- Animals with taxonomy (class › order › family), NCBI Taxonomy links, filters by kind (companion / livestock / wildlife) and group
- Anatomy grouped by body system, with species-specific notes (for example “ruminants have a four-compartment stomach”)
- Diseases with cause, zoonotic and WOAH-listed flags, signs, affected species and organs, transmission and prevention
- Symptom lookup: pick signs (and optionally a species) and see matching diseases ranked by how many signs they share
- Site-wide search across animals, diseases, organs and signs
- Review badges, source lists and an “About & sources” page that lists every source and its license

**Admin** (`/admin`)

- Create, edit and delete animals, diseases, body parts, sources, groups, body systems and signs
- Link records with optional species-specific notes; cite sources; set review status (draft / not yet reviewed / reviewed)
- Dashboard with review progress and “gaps to fill” (records without sources, animals without anatomy, …)
- Draft records are hidden from the public until you publish them

## Data model

```text
categories ─┐
            └─< animals >──< animal_body_parts >──┐
                   │                              ├── body_parts >── body_systems
                   │                 disease_body_parts >──┘
                   └──< animal_diseases >── diseases >──< disease_symptoms >── symptoms

animals / diseases / body_parts  >──<  animal_sources / disease_sources / body_part_sources  >──  sources
```

Identifiers from public ontologies are stored so the data stays interoperable:

| Field | Ontology |
|---|---|
| `animals.ncbi_taxon_id` | [NCBI Taxonomy](https://www.ncbi.nlm.nih.gov/taxonomy) |
| `body_parts.uberon_id` | [Uberon](https://obofoundry.org/ontology/uberon.html) anatomy ontology |
| `diseases.disease_ontology_id` | [Disease Ontology](https://disease-ontology.org/) (only where a matching term exists) |

## Tech stack

PHP 8.1+ (procedural, `strict_types`), MySQL / MariaDB via PDO prepared statements, Bootstrap 5 (CDN), a little vanilla JavaScript.

## Quick start (XAMPP)

1. Copy this folder into `htdocs` (for example `C:\xampp\htdocs\biodiversity-system`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Install the database and sample data from a terminal in the project folder:

   ```powershell
   C:\xampp\php\php.exe tools\install.php
   ```

4. Create an administrator account (a random password is generated and printed once):

   ```powershell
   C:\xampp\php\php.exe tools\create-admin.php admin
   ```

5. Open <http://localhost/biodiversity-system/> and sign in at `/admin/login.php`.

Prefer phpMyAdmin? Import `database/schema.sql`, then `database/seed.sql` (optional sample data), then run step 4.

`tools/install.php` refuses to touch a database that already has tables. Use `--force` to drop and recreate everything (**all data is lost**), or `--no-seed` to install the schema without sample data.

### Upgrading from the first version of this project

The earlier version had a different, flat schema (animals and categories only) and only sample data. Run `tools\install.php --force` to replace it. Your own animal records are not migrated automatically; re-enter them in the admin area or export them first.

## Configuration

Defaults match a stock XAMPP install (`root`, empty password, database `biodiversity_system`). Override them without editing tracked files:

- create `config/config.local.php` (git-ignored):

  ```php
  <?php
  return ['db_user' => 'bio', 'db_pass' => 'a-strong-password', 'db_host' => '127.0.0.1'];
  ```

- or set the environment variables `BIO_DB_HOST`, `BIO_DB_PORT`, `BIO_DB_NAME`, `BIO_DB_USER`, `BIO_DB_PASS`.

## Data and trust

Each animal, disease and body part has a **review status**:

| Status | Meaning |
|---|---|
| `draft` | Work in progress. Visible only to administrators. |
| `unreviewed` | Public, shown with a *Not yet expert-reviewed* badge. A starting point to verify against its sources. |
| `reviewed` | Public. A qualified reviewer has checked it against its cited sources. |

Ground rules (details in [CONTRIBUTING.md](CONTRIBUTING.md)):

- Cite sources. Write in your own words.
- **Never copy text from copyrighted manuals or books** (for example the Merck Veterinary Manual). Link to them instead; the sources table records the license and “link only” where it applies.
- Prefer public identifiers (NCBI Taxonomy, Uberon, Disease Ontology) and check them against the registry before adding them.
- Conservation statuses change. Check the [IUCN Red List](https://www.iucnredlist.org/) before relying on them.

### Known limitations

- Small coverage: domestic mammals and poultry are the most complete.
- Anatomy uses vertebrate terms; invertebrates and fish-specific structures (gills, swim bladder) are not yet covered.
- No treatment, dosage or drug information, by design.
- No edit history / audit log yet.

## Security

Built in: prepared statements everywhere, output escaping, CSRF tokens on every admin form, `HttpOnly`/`SameSite` session cookie, session idle timeout, no default account, and hardened image uploads (the file type is detected from the file contents, the stored extension is chosen from that type, and the uploads folder has an `.htaccess` that blocks script execution).

Before putting an installation on the internet:

- Serve it over **HTTPS** and use a strong, unique admin password.
- Create a dedicated MySQL user with access to this database only (do not use `root`).
- Turn off `display_errors` in `php.ini`.
- Consider restricting `/admin` by IP address or HTTP basic authentication. There is no built-in login rate limiting beyond a short delay after a failed attempt.
- Make sure `assets/images/uploads/` is not allowed to execute scripts if you do not use Apache (the `.htaccess` only works on Apache with `AllowOverride` enabled).
- Take regular database backups.

To put the site online (GitHub itself cannot run PHP), see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

Please report security problems privately, as described in [SECURITY.md](SECURITY.md), rather than in a public issue.

## Project structure

```text
biodiversity-system/
├── index.php, animals.php, animal-details.php      public pages
├── body-parts.php, body-part-details.php
├── diseases.php, disease-details.php
├── symptoms.php, search.php, about.php
├── endangered.php            redirects to animals.php?threatened=1 (old links keep working)
├── admin/                    admin area (login, dashboard, CRUD, sources, lists)
├── config/                   config.php, connection.php, db.php
├── database/                 schema.sql, seed.sql
├── includes/                 header, navbar, footer, shared helpers
├── assets/                   css, js, images (uploads/ is git-ignored)
├── tools/                    install.php, create-admin.php (command line only)
├── docs/DEPLOYMENT.md        how to host it (GitHub Pages cannot run PHP)
├── .github/                  CI workflow, issue and pull-request templates
├── .htaccess                 Apache hardening (blocks config/, database/, includes/, tools/, dotfiles)
├── LICENSE                   MIT (code)
├── SECURITY.md               how to report vulnerabilities
├── DATA_LICENSE.md           CC BY 4.0 (original data and text)
└── CONTRIBUTING.md
```

## Ideas for the future

Importers for NCBI Taxonomy, Uberon and Disease Ontology; a read-only JSON API; multi-user roles with an edit history and a reviewer workflow; anatomy terms for fish and invertebrates; translations; automated tests.

## License

Code: [MIT](LICENSE). Original data and text: [CC BY 4.0](DATA_LICENSE.md). Third-party sources keep their own licenses, listed on the *About & sources* page.
