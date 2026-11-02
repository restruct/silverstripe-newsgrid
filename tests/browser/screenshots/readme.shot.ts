import { test, expect, type Page } from '@playwright/test';
import * as path from 'path';

// Generates the README screenshots in docs/images/. Not a test suite: run it with
// playwright.screenshots.config.ts (see README.md next to this file). The content comes from the
// browser fixtures: NgShowcaseAdmin seeds a "News" section with seven news items (featured images
// drawn with GD), and NgBResetAdmin's layout action renders the section's front-end Layout template.

const IMAGES = path.join(__dirname, '..', '..', '..', 'docs', 'images');

// The scratch host has no theme. The module's Layout templates use Bootstrap 5 classes, so the
// front-end shot loads Bootstrap's stylesheet, as a Bootstrap theme would. Presentation only.
const BOOTSTRAP_CSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';

type Showcase = { id: number; link: string; items: Record<string, number> };

/** Seed the demo content (every call starts over) and return the section's ID and its items. */
async function seed(page: Page): Promise<Showcase> {
    const response = await page.request.get('/admin/ng-showcase/seed');
    expect(response.ok(), await response.text()).toBe(true);
    return (await response.json()) as Showcase;
}

/** Wait until every <img> on the page has loaded. */
async function imagesLoaded(page: Page): Promise<void> {
    await expect
        .poll(() => page.evaluate(() => Array.from(document.images).every((i) => i.complete && i.naturalWidth > 0)), {
            message: 'all images loaded',
        })
        .toBe(true);
}

/** Wait for the WYSIWYG editor of the Content field (TinyMCE renders into an iframe). */
async function editorReady(page: Page): Promise<void> {
    await expect(page.locator('#Form_EditForm_Content_Holder iframe')).toBeVisible();
}

/** No hover state and no focus ring in the shot. */
async function settle(page: Page): Promise<void> {
    await page.mouse.move(0, 0);
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    await page.waitForTimeout(500);
}

/**
 * Collapse the site tree panel with its own toggle, so the edit form gets the window's width.
 * The CMS shots leave the site tree out on purpose: on Silverstripe 6 it lists the news items once
 * a page is open in the editor (see the README's note on admintweaks), so a shot of it would show
 * either that bug or a state an editor does not keep.
 */
async function collapseTree(page: Page): Promise<void> {
    // The site tree panel's « button (the main menu's own toggle is labelled differently)
    await page.getByRole('button', { name: 'Collapse panel' }).click();
    await page.waitForTimeout(500);
}

/** The edit panel only: from the edit form's left edge to the window's right edge, down to `bottom`. */
async function shootPanel(page: Page, file: string, bottom: number): Promise<void> {
    const panel = (await page.locator('#Form_EditForm').boundingBox())!;
    const width = page.viewportSize()!.width;
    await page.screenshot({
        path: path.join(IMAGES, file),
        clip: { x: Math.floor(panel.x), y: 0, width: width - Math.floor(panel.x), height: Math.ceil(bottom) },
    });
}

let showcase: Showcase;

test.beforeAll(async ({ browser }, testInfo) => {
    const context = await browser.newContext(testInfo.project.use);
    const page = await context.newPage();
    showcase = await seed(page);
    await context.close();
});

test('CMS: the News section with its news items in a grid below Content', async ({ page }) => {
    // Tall enough for the whole grid of seven items.
    await page.setViewportSize({ width: 1280, height: 1300 });
    await page.goto(`/admin/pages/edit/show/${showcase.id}`);
    const grid = page.locator('#Form_EditForm_ChildPages');
    await expect(grid.locator('tbody tr.ss-gridfield-item')).toHaveCount(7);
    await editorReady(page);
    await collapseTree(page);
    await settle(page);

    const box = (await grid.boundingBox())!;
    await shootPanel(page, 'cms-news-section.png', box.y + box.height + 24);
});

test('CMS: a news item, with its Date and featured image above Content', async ({ page }) => {
        await page.goto(`/admin/pages/edit/show/${showcase.items['Volunteers wanted for the summer festival']}`);
    const form = page.locator('#Form_EditForm');
    await expect(form.locator('#Form_EditForm_Date')).toBeVisible();
    await expect(form.locator('#Form_EditForm_FeaturedImages_Holder .uploadfield-item--image')).toBeVisible();
    await editorReady(page);
    await collapseTree(page);
    await imagesLoaded(page);
    await settle(page);

    // Down to just below the Content editor: the fields under it add nothing here.
    const content = (await form.locator('#Form_EditForm_Content_Holder').boundingBox())!;
    await shootPanel(page, 'cms-news-item.png', Math.min(800, content.y + content.height + 16));
});

test('Front end: the News section page, newest first, with page links', async ({ page }) => {
    // per_page=4: NewsGridHolder.items_per_page for this render, so the seven items take two pages
    await page.goto(`/admin/ng-reset/layout?id=${showcase.id}&per_page=4`);
    await page.addStyleTag({ url: BOOTSTRAP_CSS });
    // Some room around the layout, as a page body would give it
    await page.addStyleTag({ content: 'main.layout { padding: 8px 0 24px; }' });
    await expect(page.locator('.pagination .page-item')).not.toHaveCount(0);
    await imagesLoaded(page);
    await settle(page);
    await page.locator('main.layout').screenshot({ path: path.join(IMAGES, 'frontend-news-section.png') });
});
