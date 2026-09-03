# Local Development

This package is developed with Extended Testbench, not a full Laravel app.

- `php artisan <command>` boots the Testbench skeleton with this package's service provider.
- Run the complete local gate with `composer check`; use `composer test` for Pest alone.
- Run `composer fix` before committing. It applies Rector and Pint.
- When changing package scaffolding, verify it with `php artisan package:init --no-workbench --no-browser --phpstan-level=8 --rector --pint --check --no-interaction`.
- `compose.yml` can run a local `pdf-ua-api` instance for manual integration checks. Automated tests fake the HTTP boundary and must not depend on a live service.
- Regenerate `CLAUDE.md` after editing files in `.ai/guidelines/` with `php artisan boost:update`.

## Comments

- Code must be self-explanatory: reach for clear names, small functions, and types before a comment.
- Do not add comments. A comment is a last resort and explains only *why* something is done, never *what* the code does.
- When you encounter an obsolete, redundant, or "what" comment, delete it.
