# Changelog

## 3.1.0 (unreleased)

Silverstripe 5 and 6 from one line (`master`). Silverstripe 4 is not supported; projects on it can
stay on the `2.0.x` tags.

### Upgrading

- **From 2.0.x (Silverstripe 5):** change your constraint to `^3.1`. Nothing to migrate in the
  database. What you will notice is listed under "Changed" below.
- **From 3.0.x (Silverstripe 6):** a `^3` constraint picks this up. The news items grid moves back to
  the Main tab (fixed issue #1, below).

### Fixed

- **The news items grid is back on the Main tab, directly below Content** (issue #1, "Lost
  bugfixes"). The Silverstripe 6 work in 3.0.x had commented this out, so the grid sat on
  Lumberjack's separate tab. The restored code uses the current argument order of
  `FieldList::insertAfter()`, the fix that 2.0.10 carried on the `ss345` branch only.
- **Filterable item properties show again** on news item pages and in the News section's list,
  when filterablearchive is installed. 3.0.x guarded them on a holder-only method, so in an item's
  scope they never rendered.
- **`NewsItemTile.ss` (used by `BlockNewsItems`) no longer throws without filterablearchive**: its
  `FilterableProperties` include is now guarded like the others.
- **Editing a News section no longer fatals on Silverstripe 6.** admintweaks 4.1.0's add-new
  button calls `SiteTree::page_type_classes()`, which Silverstripe 6 removed. On SS6 the grid now
  uses Lumberjack's own add-new button, which offers the same page type. This is a workaround until
  admintweaks is fixed.
- **English CMS strings load.** `lang/en.yml` was keyed `nl:`, so an English CMS showed the Dutch
  defaults (the grid was titled "Nieuwsberichten").
- **Page type descriptions are translated on Silverstripe 6**, which reads only the
  `CLASS_DESCRIPTION` key; it is now provided next to the legacy `DESCRIPTION`.
- **`BlockNewsItems::NewsSectionLink()` no longer errors when there is no News section.** With a
  label set and no `NewsGridHolder` in the site, it assigned the label on `null`; it now returns
  `null`, so the block renders without the "all news" link.
- **The SS3 class name remapping runs on Silverstripe 6.** It moved from `DatabaseAdmin` to
  `DbBuild`; `_config/upgrade.yml` now sets it on whichever class exists.

### Changed

- Requires `silverstripe/framework ^5 || ^6`, `silverstripe/lumberjack ^3 || ^4`,
  `restruct/silverstripe-admintweaks ^3 || ^4`, `restruct/silverstripe-featuredimages ^4 || ^5`
  and PHP `^8.1`.
- Page types declare both the Silverstripe 6 statics (`class_description`, `cms_icon`) and the
  Silverstripe 5 ones (`description`, `icon`).
- For projects coming from 2.0.x, carried over from 3.0.x: the grid shows Title (plus Date when
  `managed_object_date_field` is set, and Scheduling only when softscheduler is installed) instead
  of merging into the default summary fields; the filterablearchive includes in the templates
  render only when that module is installed.
- `restruct/silverstripe-blockbase` is listed under `suggest`. blockbase itself still requires
  Silverstripe 4 (1.0.8, `dev-main`), so `BlockNewsItems` is not available until a blockbase
  release supports Silverstripe 5/6; the README says so.
- Adds a behavioural test suite (`tests/`, 46 tests; 3 of them skip on a host with
  filterablearchive, see the README). It has been run locally on Silverstripe 5.4 and 6.2
  (PHP 8.3). A CI workflow is added for Silverstripe 5 (PHP 8.1, 8.3) and Silverstripe 6 (PHP 8.3,
  8.4) against MariaDB 11.4, plus a real `dev/build` / `db:build`; **it has not run yet**.
- `Extensions\CustomLumberjack` (not applied by default: `_config/config.yml` leaves it commented
  out) hides news items from the CMS in more places since 3.0.0. `shouldFilter()` now filters on any
  `CMSMain` controller for the `index`, `show`, `treeview` and `getsubtree` actions; 2.0.x filtered
  only on `CMSPagesController` for `treeview` and `getsubtree` (Silverstripe 6 has no
  `CMSPagesController`). `listview` is still not filtered. If your project applies the extension,
  check that the CMS still shows news items where you expect them.
- Adds `.gitattributes`: dist installs no longer ship `tests/` or `.github/`.
- Adds the `funding` property to `composer.json`.

### Still open

- Issue #2, "Archiving causes timeout/hanging": not addressed in this release.

## 3.0.1, 3.0.0

Silverstripe 6 only (`^6`). Superseded by 3.1.0, which also carries the fixes above.

## 2.0.x

Silverstripe 4 and 5 (`^4 || ^5`). 2.0.10 and 2.0.11 were tagged from the `ss345` branch.
