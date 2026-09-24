Newspages managed from a GridField to prevent SiteTree clutter
==============================================================

*Maintained by [Restruct](https://github.com/restruct). If this module saves you time, you can
[support ongoing maintenance](https://github.com/sponsors/restruct).*

This module manages newsitems in a gridfield to prevent massive sitetrees.

It adds two page types:

- **News section** (`Restruct\SilverStripe\NewsGrid\NewsGridHolder`): a page whose news items are
  listed in a grid on its Main tab, directly below Content, instead of in the CMS site tree.
- **News item** (`Restruct\SilverStripe\NewsGrid\NewsGridPage`): a page with a `Date` and one or
  more featured images. Items can only be created inside a News section and are hidden from the
  CMS site tree.

## Requirements

* Silverstripe 5 or 6
* PHP 8.1 or newer
* [silverstripe/lumberjack](https://github.com/silverstripe/silverstripe-lumberjack),
  [restruct/silverstripe-admintweaks](https://github.com/restruct/silverstripe-admintweaks) and
  [restruct/silverstripe-featuredimages](https://github.com/restruct/silverstripe-featuredimages)
  (installed automatically)
* Your project's own `Page` and `PageController` classes: both page types extend them.

## Installation

```
composer require restruct/silverstripe-newsgrid
```

Then run `dev/build` (Silverstripe 5) or `sake db:build` (Silverstripe 6), and flush.

## Version compatibility

| Branch | Module version | Silverstripe | PHP |
|--------|----------------|--------------|-----|
| `main` | `3.1.x` | `^5 \|\| ^6` | `^8.1` |
| (tags only) | `3.0.x` | `^6` | not declared |
| `v2` | `2.0.x` | `^4 \|\| ^5` | not declared |

Silverstripe 4 reached end of life in April 2025 and is no longer supported or tested here. Projects
still on it can stay on the `2.0.x` tags, which remain available. The Silverstripe 3 predecessor
was published as `micschk/silverstripe-newsgrid`.

`main` is the maintained line: it supports every Silverstripe version this module still targets.

**`composer.json` is the source of truth** for exact constraints; this table is a quick reference.

Upgrading from 2.x or 3.0.x? See [CHANGELOG.md](CHANGELOG.md).

## Configuration

Everything below is set by the module's own `_config/config.yml`; override it in your project's
YAML as needed.

```yaml
Restruct\SilverStripe\NewsGrid\NewsGridHolder:
  # Lumberjack variant from admintweaks that manages the child pages in a grid
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SelectiveLumberjack
  # Core Hierarchy setting: keep news items out of the CMS site tree
  hide_from_cms_tree:
    - Restruct\SilverStripe\NewsGrid\NewsGridPage
  allowed_children:
    - Restruct\SilverStripe\NewsGrid\NewsGridPage

Restruct\SilverStripe\NewsGrid\NewsGridPage:
  extensions:
    - Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension
```

| Option | On | Default | Effect |
|--------|----|---------|--------|
| `managed_object_date_field` | `NewsGridHolder` | not set (set to `Date` when filterablearchive is installed) | When set, the news items grid shows that field as a "Date" column. Without it the grid shows the title only. |
| `hide_from_cms_tree` | `NewsGridHolder` | `[NewsGridPage]` | Page classes left out of the CMS site tree under a News section. |
| `default_sort` | `NewsGridPage` | `Date DESC` | News items are listed newest first. |
| `apply_sortable` | `NewsGridHolder` | `false` | **No effect with this module alone.** Only `micschk/silverstripe-gridfieldpages` reads it (its `GridFieldPageHolderExtension`, Silverstripe 4 only), which `_config/config.yml` leaves commented out. With that extension applied, `true` adds drag-and-drop ordering to its pages grid. |

The CMS stylesheet `client/css/newsgridpages.css` is added to every admin screen through
`LeftAndMain.extra_requirements_css`.

## Optional integrations

Each of these is picked up automatically when the package is installed, and ignored otherwise.

- **[restruct/silverstripe-filterablearchive](https://github.com/restruct/silverstripe-filterablearchive)**:
  date archive, categories and tags. Applies its holder, holder controller and item extensions,
  sets `managed_object_date_field: Date` and `pagination_control_tab: Root.Filtering`, and the
  templates show the filter, the item properties and pagination.
- **[restruct/silverstripe-softscheduler](https://github.com/restruct/silverstripe-softscheduler)**:
  scheduled publication and expiry. Applies `EmbargoExpiryExtension` to news items, adds a
  "Scheduling" column to the grid, and moves the scheduling fields below Content.
- **[restruct/silverstripe-blockbase](https://github.com/restruct/blockbase)**: declares the
  `BlockNewsItems` block, which shows the most recent news items (optionally limited to one
  filterablearchive category) with an optional link to the first News section. Without blockbase
  the class is not declared at all. **Not usable yet on Silverstripe 5 or 6:** blockbase's
  releases so far (up to 1.0.8, and `dev-main`) require Silverstripe 4, so this integration needs
  a blockbase release that supports Silverstripe 5/6.

## Public API

| Method | On | Returns |
|--------|----|---------|
| `getLumberjackPagesForGridfield()` | `NewsGridHolder` | The News section's own news items as `NewsGridPage` records, so the grid can sort on `Date`. |
| `getLumberjackTitle()` | `NewsGridHolder` | The grid's title (translatable, `NEWSGRID.NewsItems`). |
| `formattedPublishDate()` | `NewsGridPage` | The item's `Date` through `Format('d MMM y')` (a CLDR pattern, not a PHP `date()` one): day, abbreviated month name and calendar year, e.g. `2 Jan 2026`. The month name follows the site locale (`nl_NL`: `2 jan 2026`). Before 3.1.0 it used `d M Y`, which rendered `2 1 2026` (month number, week-year). |
| `DateFieldComment()` | `NewsGridPage` | `(x minutes ago)` for items dated within the last hour; requires filterablearchive. |
| `RecentNewsItems($limit = 3)` | `BlockNewsItems` | The most recent news items, optionally filtered by category. |
| `NewsSectionLink()` | `BlockNewsItems` | The first News section, labelled for the "all news" link, or `null` when no label is set or no News section exists. |

In templates, `$NoAutoImage` on a news item tells the layout not to insert the featured image into
the content automatically.

## Running the tests

The suite needs a booted Silverstripe project that provides `Page` and `PageController`. Install the
module as a symlinked path repository (a dist install contains no tests), then:

```
# Silverstripe 5 (PHPUnit 9): the path must come before flush=1
vendor/bin/phpunit vendor/restruct/silverstripe-newsgrid/tests flush=1

# Silverstripe 6 (PHPUnit 11)
SS_PHPUNIT_FLUSH=1 vendor/bin/phpunit vendor/restruct/silverstripe-newsgrid/tests
```

`.github/workflows/ci.yml` builds exactly such a host project for each supported major.

Two checks skip themselves on purpose, so the test count depends on what the host has installed:

- `NewsGridTemplatesTest` (3 tests) is skipped when restruct/silverstripe-filterablearchive is
  installed. It checks that the templates render *without* that module (its includes are guarded
  so a missing template does not throw), which a host that has it cannot show. The other side, the
  includes rendering when filterablearchive is present, is covered by `NewsGridTemplateGuardTest`
  with a stand-in, which runs either way.
- `ModuleConfigTest::testBlockIsNotDeclaredWithoutBlockbase` is skipped when blockbase is installed.
  blockbase currently requires Silverstripe 4, so on Silverstripe 5 and 6 it always runs.
