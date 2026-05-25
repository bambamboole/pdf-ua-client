# Local Development

This package is developed with Orchestra Testbench, not a full Laravel app.

- `artisan` at the repo root is a symlink to `vendor/bin/testbench`, so `php artisan <command>` boots the Testbench skeleton with this package's service provider and the `workbench/` app. Use it for all Artisan commands.
- Run the test suite with `composer test` (Pest) or `./vendor/bin/pest`.
- Serve the workbench app with `composer serve`.
- Run the `pdf-ua-api` service locally with `docker compose up -d` (image `bambamboole/pdf-ua-api`, served on `http://localhost:8888`). Point the client at it with `PDF_UA_API_URL=http://localhost:8888`; integration tests that need the live API skip when it is unreachable.
- The golden-PDF visual regression in `RenderFixtureTest` rasterizes PDFs with Imagick and diffs them per page. It needs `ext-imagick`, Ghostscript (`brew install ghostscript`), and a reachable `PDF_UA_API_URL`, and skips when any is missing. Regenerate the committed golden PDFs with `UPDATE_PDF_FIXTURES=1` (requires the live API); failures dump diff artifacts to `tests/.pdf-diff/`.
- Always run `composer fix` before committing. It applies every lint/format fixer (Pint, oxlint --fix, oxfmt) that CI checks.
- The AI tooling overrides for Boost live in `workbench/app/Support/` and are wired in `Workbench\App\Providers\WorkbenchServiceProvider`. They point Boost at the package root instead of the Testbench skeleton; they never ship with the published package.
- Regenerate `CLAUDE.md` after editing files in `.ai/guidelines/` with `php artisan boost:update`.

## Comments

- Code must be self-explanatory: reach for clear names, small functions, and types before a comment.
- Do not add comments. A comment is a last resort and explains only *why* something is done, never *what* the code does.
- When you encounter an obsolete, redundant, or "what" comment, delete it.
