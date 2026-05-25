# Local Development

This package is developed with Orchestra Testbench, not a full Laravel app.

- `artisan` at the repo root is a symlink to `vendor/bin/testbench`, so `php artisan <command>` boots the Testbench skeleton with this package's service provider and the `workbench/` app. Use it for all Artisan commands.
- Run the test suite with `composer test` (Pest) or `./vendor/bin/pest`.
- Serve the workbench app with `composer serve`.
- Always run `composer fix` before committing. It applies every lint/format fixer (Pint, oxlint --fix, oxfmt) that CI checks.
- The AI tooling overrides for Boost live in `workbench/app/Support/` and are wired in `Workbench\App\Providers\WorkbenchServiceProvider`. They point Boost at the package root instead of the Testbench skeleton; they never ship with the published package.
- Regenerate `CLAUDE.md` after editing files in `.ai/guidelines/` with `php artisan boost:update`.

## Comments

- Code must be self-explanatory: reach for clear names, small functions, and types before a comment.
- Do not add comments. A comment is a last resort and explains only *why* something is done, never *what* the code does.
- When you encounter an obsolete, redundant, or "what" comment, delete it.
