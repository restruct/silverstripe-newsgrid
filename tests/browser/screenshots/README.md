# README screenshots

`readme.shot.ts` writes the PNGs in `docs/images/` that the module README shows. It is not a test
suite: it runs with its own config, `playwright.screenshots.config.ts`, which the browser tests and
CI never load, so a normal test run never rewrites the images. Never edit the PNGs by hand;
regenerate them.

What it needs:

- a Silverstripe 6 host with this module installed (no optional integrations, but with
  `silverstripe/htmleditor-tinymce`, which recipe-cms 6 does not include, so the Content field is
  the WYSIWYG editor a real project has) and the browser fixtures from `tests/browser/fixtures/`
  copied into its `app/src/`, then the database built.
  `NgShowcaseAdmin` seeds the content when the script calls `/admin/ng-showcase/seed`: an "About
  us" page and a published "News" section with seven news items, each with a featured image drawn
  with GD. The front-end shot renders the section through `NgBResetAdmin`'s layout action (the host
  has no theme) and loads Bootstrap 5's stylesheet from jsDelivr, so the capture needs network access;
- `BROWSER_TARGET_URLS` pointing at that host (only the `ss6` target is used, else the first).

The shared runner in the maintainers' workspace does all of that:

```bash
SS6_EXTRA_REQUIRE="silverstripe/htmleditor-tinymce:^1" BROWSER_HOSTS_DIR=~/Library/Caches/ssmods-browser-hosts-screenshots \
  BROWSER_ONLY=ss6 tools/browser/run.sh silverstripe-newsgrid --config=playwright.screenshots.config.ts
```

The separate `BROWSER_HOSTS_DIR` keeps the extra package out of the host the browser tests use.

The CMS shots show the edit panel with the site tree collapsed (see `readme.shot.ts` for why).

Against a host you started yourself, from `tests/browser/`:

```bash
npm ci
BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8896" npx playwright test --config=playwright.screenshots.config.ts
```
