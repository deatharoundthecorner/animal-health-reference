# Contributing

Thank you for helping. The most valuable contributions are **corrections and additions from veterinarians and biologists**, followed by code improvements.

## Ways to contribute

- **Fix or review a record.** Check an animal, disease or body-part entry against its sources and correct it. If you are qualified to judge it, say so in your pull request or issue so a maintainer can mark it *Expert-reviewed*.
- **Add data.** New diseases, anatomy notes, species, signs, sources.
- **Improve the software.** Bug fixes, accessibility, performance, importers, tests.

## Rules for data

1. **Cite your sources.** Every disease, animal and body part should link to at least one source in the `sources` table. Record the publisher and the license.
2. **Write in your own words.** Do **not** paste or closely paraphrase text from copyrighted material, especially commercial manuals and textbooks (for example the Merck Veterinary Manual, Plumb’s, BSAVA manuals). Link to them as sources instead and mark them “link only”.
3. **No treatment or dosage advice.** This is a reference for recognising and understanding conditions, not a clinical protocol. Wrong dosing information can harm animals.
4. **Be precise about species.** If a statement is true only for some species, put it in the species-specific note, not in the general description.
5. **Use public identifiers, and verify them.** Look up NCBI Taxonomy, Uberon and Disease Ontology IDs in their registries and confirm that the label matches what you mean. Leave the field empty if there is no matching term; do not guess. (For example, the Disease Ontology term “mastitis” describes the human breast condition, so it is not used for bovine mastitis.)
6. **Mark the review status honestly.** New and imported records are `unreviewed`. Only a qualified reviewer who has checked the record against its sources should set `reviewed`.
7. **Say what is uncertain.** Prefer “associated with” to “caused by” when the evidence is incomplete, and keep conservation statuses dated or linked to the IUCN Red List.
8. **Respect animal and human safety.** Flag zoonotic and notifiable diseases clearly.

## Rules for code

- PHP 8+, `declare(strict_types=1)`, procedural style that a student can follow.
- Database access only through PDO prepared statements. Use `run_query()` from `includes/functions.php` (integers are bound as integers, which MySQL needs for `LIMIT`). Each named placeholder may appear once per query.
- Escape every value that is printed with `e()`. Never echo user input or database text unescaped.
- Every admin form that changes data must include `csrf_field()` and call `require_csrf()` on POST.
- Uploaded files: detect the type from the file content, choose the stored extension yourself, and never use the client-supplied file name.
- Table and column names that are interpolated into SQL must come from code constants, never from user input.
- Keep admin-only code in `admin/`. Include `admin/_auth.php` first on every admin page.

## Before you open a pull request

- Lint all PHP files: `php -l path/to/file.php` (or run it over the whole tree).
- Install into a **scratch database** (not your real one) with `BIO_DB_NAME=bio_test php tools/install.php --force` and click through the pages you changed, including the admin forms with valid and invalid input.
- If you changed the schema, update `database/schema.sql`, `database/seed.sql` and the data-model section of `README.md`.
- If you added sample data, keep `review_status = 'unreviewed'` and add its sources.

## Reporting security issues

Please do not open a public issue for security problems. Follow [SECURITY.md](SECURITY.md) to report them privately.

## License

By contributing you agree that your code is released under the [MIT license](LICENSE) and your data and text under [CC BY 4.0](DATA_LICENSE.md).
