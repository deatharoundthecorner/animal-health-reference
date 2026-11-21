## What does this change?

<!-- A short description. Link the issue it closes, if any. -->

## Checklist

**For code changes**
- [ ] `php -l` passes on every file I touched
- [ ] I tried the change in a scratch database (not my real one), including invalid input in any form I touched
- [ ] Output is escaped with `e()`, queries use prepared statements, and admin forms use `csrf_field()` / `require_csrf()`
- [ ] If I changed the schema, I updated `database/schema.sql`, `database/seed.sql` and the README

**For data changes**
- [ ] Every new or changed statement has a source (added to the Sources table)
- [ ] I wrote it in my own words and did not copy text from copyrighted manuals or books
- [ ] No treatment or dosage advice
- [ ] Ontology IDs (NCBI Taxonomy, Uberon, Disease Ontology) were checked against the registry, or left empty
- [ ] New records are `unreviewed` unless a qualified reviewer checked them (say who, below)

**Reviewer (for data marked "Expert-reviewed")**
<!-- Your name or handle and your qualification, e.g. "DVM, small-animal practice". -->
