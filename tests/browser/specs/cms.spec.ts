import { test, expect, gridRows, mainTabFields, newsGrid, openInCms, reseed, saveDraft } from './support';

// The News section in the CMS: its news items are managed in a grid on its Main tab (Lumberjack,
// through admintweaks' SelectiveLumberjack) instead of in the site tree.

test('a News section lists its news items, newest first, in a grid on the Main tab directly below Content', async ({ page }) => {
    const section = await reseed(page, 'Section grid');
    const form = await openInCms(page, section.id);

    // Issue #1 (3.1.0): the grid sits right after Content, not on a separate ChildPages tab.
    const fields = await mainTabFields(form);
    expect(fields.slice(-2)).toEqual(['Form_EditForm_Content_Holder', 'Form_EditForm_ChildPages']);
    await expect(page.locator('#tab-Root_ChildPages, [aria-controls="Root_ChildPages"]')).toHaveCount(0);

    // Newest first (default_sort Date DESC); Title and the simplified state column only: no Date
    // column without managed_object_date_field (set by filterablearchive).
    const grid = newsGrid(page);
    expect(await gridRows(grid)).toEqual([
        ['Draft item', 'Draft'],
        ['Modified item', 'Published, Modified'],
        ['Published item', 'Published'],
    ]);
    await expect(grid.locator('tbody tr.ss-gridfield-item').first().locator('td')).toHaveCount(3);
    await expect(grid.locator('td.col-Date')).toHaveCount(0);

    // GridFieldSimpleSiteTreeState: an icon and the state, no dates; "Modified" as its own label.
    const modified = grid.locator('tbody tr.ss-gridfield-item', { hasText: 'Modified item' }).locator('td.gridfield-icon');
    await expect(modified.locator('i.font-icon-check-mark-circle')).toHaveCount(1);
    await expect(modified.locator('span.modified')).toHaveText('Modified');
    await expect(grid.locator('tbody tr.ss-gridfield-item', { hasText: 'Draft item' }).locator('td.gridfield-icon i.font-icon-pencil')).toHaveCount(1);

    // The module's admin stylesheet is on every admin screen (LeftAndMain.extra_requirements_css).
    await expect(page.locator('link[href*="/silverstripe-newsgrid/client/css/newsgridpages.css"]')).toHaveCount(1);
});

test('the News section is in the site tree with the module\'s icon', async ({ page }) => {
    const section = await reseed(page, 'Section icon');
    await openInCms(page, section.id);
    const node = page.locator(`.cms-tree li[data-id="${section.id}"]`);
    await expect(node).toHaveCount(1);
    // The page icon (SS5 $icon, SS6 $cms_icon) is the module's newsholder.png.
    const icon = node.locator('.text > span').first();
    expect(await icon.evaluate((el) => getComputedStyle(el).backgroundImage)).toContain('/silverstripe-newsgrid/client/images/newsholder.png');
});

test('news items stay out of the site tree (https://github.com/restruct/silverstripe-admintweaks/issues/62)', async ({ page }, testInfo) => {
    // hide_from_cms_tree works through admintweaks' SelectiveLumberjack, whose filter never applies
    // on Silverstripe 6 (it checks for CMSPagesController, which cms 6 does not have): upstream bug.
    test.fixme(testInfo.project.name === 'ss6', 'admintweaks#62: SelectiveLumberjack never filters on Silverstripe 6');
    const section = await reseed(page, 'Section tree');
    await openInCms(page, section.id);
    const tree = page.locator('.cms-tree');
    await expect(tree.locator(`li[data-id="${section.id}"]`)).toHaveCount(1);
    for (const [title, id] of Object.entries(section.items)) {
        await expect(tree.locator(`li[data-id="${id}"]`), `${title} not in the tree`).toHaveCount(0);
    }
});

test('"Add new NewsItem" creates a news item inside the section, which then shows in its grid', async ({ page }) => {
    const section = await reseed(page, 'Section add');
    await openInCms(page, section.id);

    await newsGrid(page).locator('button#action_add').click();
    // The new item opens in the page editor.
    await expect(page).toHaveURL(/\/admin\/pages\/edit\/show\/\d+$/);
    const form = page.locator('form#Form_EditForm');
    await expect(form.locator('input[name="Title"]')).toHaveValue(/^New NewsItem/);
    const newId = Number(page.url().match(/(\d+)$/)![1]);
    expect(Object.values(section.items)).not.toContain(newId);

    // The "do not auto-insert the image" option sits right after the featured images.
    const fields = await form.locator('#Root_Main > [id$="_Holder"]').evaluateAll((els) => els.map((e) => e.id));
    const at = fields.indexOf('Form_EditForm_FeaturedImages_Holder');
    expect(at).toBeGreaterThanOrEqual(0);
    expect(fields[at + 1]).toBe('Form_EditForm_NoAutoImage_Holder');

    await form.locator('input[name="Title"]').fill('Breaking news');
    await saveDraft(page);

    // Back on the section: the new item is listed (dated today, so first) as a draft.
    await openInCms(page, section.id);
    expect((await gridRows(newsGrid(page)))[0]).toEqual(['Breaking news', 'Draft']);
});

test('a news item\'s date can be set in the CMS, directly before Content (https://github.com/restruct/silverstripe-newsgrid/issues/8)', async ({ page }) => {
    // Issue #8: Silverstripe 5 builds page fields by hand and the module added no Date field, so
    // without filterablearchive (whose ItemExtension adds one) a news item's date could not be edited
    // there; Silverstripe 6 scaffolded it after Content. The module now places one before Content.
    const section = await reseed(page, 'Item date');
    const id = section.items['Published item'];
    const form = await openInCms(page, id);
    const fields = await mainTabFields(form);
    expect(fields[fields.indexOf('Form_EditForm_Content_Holder') - 1]).toBe('Form_EditForm_Date_Holder');
    const date = form.locator('input[name="Date"]');
    await expect(date).toHaveValue('2025-12-30');
    await date.fill('2024-02-29');
    await saveDraft(page);

    await openInCms(page, id);
    await expect(page.locator('input[name="Date"]')).toHaveValue('2024-02-29');
    // And the section's grid sorts by it: now the oldest, so last.
    await openInCms(page, section.id);
    expect((await gridRows(newsGrid(page))).map(([title]) => title)).toEqual(['Draft item', 'Modified item', 'Published item']);
});

test('clicking a row in the grid opens that news item in the page editor', async ({ page }) => {
    const section = await reseed(page, 'Section open');
    await openInCms(page, section.id);
    await newsGrid(page).locator('tbody tr.ss-gridfield-item', { hasText: 'Modified item' }).locator('td.col-Title').click();
    await expect(page).toHaveURL(new RegExp(`/admin/pages/edit/show/${section.items['Modified item']}$`));
    await expect(page.locator('form#Form_EditForm input[name="Title"]')).toHaveValue('Modified item');
});
