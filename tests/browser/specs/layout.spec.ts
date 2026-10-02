import { test, expect, openLayout, reseed } from './support';

// The module's front-end Layout templates, rendered in the page's own controller scope through the
// fixture endpoint (the scratch host has no theme to wrap them). Without filterablearchive, which
// is optional.

test('a news item page shows its title and content', async ({ page }) => {
    const section = await reseed(page, 'Layout item');
    const layout = await openLayout(page, section.items['Published item']);
    await expect(layout.locator('h1')).toHaveText('Published item');
    await expect(layout).toContainText('About Published item.');
});

test('the News section page shows its title and content', async ({ page }) => {
    const section = await reseed(page, 'Layout section');
    const layout = await openLayout(page, section.id);
    await expect(layout.locator('h1')).toHaveText('Layout section');
    await expect(layout.locator('.lead')).toHaveText('All the news.');
});

// Known bug, kept visible: the section layout loops over $PaginatedItems, which only
// filterablearchive provides, so without it no item is listed (Silverstripe 5 renders one empty
// entry instead).
test.fixme('the News section page lists its news items without filterablearchive (https://github.com/restruct/silverstripe-newsgrid/issues/7)', async ({ page }) => {
    const section = await reseed(page, 'Layout list');
    const layout = await openLayout(page, section.id);
    // Live items only, newest first.
    await expect(layout.locator('ul.list-unstyled > li h4')).toHaveText(['Modified item', 'Published item']);
});

// Known bug, kept visible: the item layout's link back reads $HolderPage, which only
// filterablearchive's ItemExtension provides, so without it the link has no URL and no text.
test.fixme('a news item page links back to its News section without filterablearchive (https://github.com/restruct/silverstripe-newsgrid/issues/7)', async ({ page }) => {
    const section = await reseed(page, 'Layout uplink');
    const layout = await openLayout(page, section.items['Published item']);
    const up = layout.locator('.newsuplink a');
    await expect(up).toHaveAttribute('href', section.link);
    await expect(up).toContainText('Layout uplink');
});
