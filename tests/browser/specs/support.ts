import { test as base, expect, type Locator, type Page, type Request } from '@playwright/test';

// Shared fixtures and helpers for the newsgrid specs.
//
// Every spec first asks the fixture reset endpoint (fixtures/NgBResetAdmin.php) for a fresh News
// section of its own, published, holding three news items in the three states its grid shows:
//   "Draft item" (2026-01-05, never published), "Modified item" (2026-01-02, published, then
//   changed on draft), "Published item" (2025-12-30, published).
// filterablearchive is not installed on the test host (it is an optional integration), so these
// specs cover the module on its own.

/**
 * test, extended with an automatic guard: every spec fails if the page logs a console error (a
 * failed request included), throws an uncaught exception, or opens a dialog. Warnings do not count.
 */
export const test = base.extend<{ guard: void }>({
    guard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message} | ${(err.stack ?? '').split('\n').slice(1, 3).join(' | ').trim()}`));
            page.on('dialog', async (dialog) => {
                if (dialog.type() !== 'beforeunload') {
                    errors.push(`unexpected ${dialog.type()}(): ${dialog.message()}`);
                }
                await (dialog.type() === 'beforeunload' ? dialog.accept() : dialog.dismiss());
            });

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors, uncaught exceptions or dialogs').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

export type Section = { id: number; link: string; items: Record<string, number> };

export async function reseed(page: Page, title: string): Promise<Section> {
    const response = await page.request.get('/admin/ng-reset/reseed', { params: { title } });
    expect(response.status(), `reseed "${title}"`).toBe(200);
    return response.json();
}

/** Open a page in the CMS page editor with a full page load. */
export async function openInCms(page: Page, id: number): Promise<Locator> {
    await page.goto(`/admin/pages/edit/show/${id}`);
    const form = page.locator('form#Form_EditForm');
    await expect(form.locator('input[name="Title"]')).toBeVisible();
    return form;
}

/** The News section's news items grid. */
export function newsGrid(page: Page): Locator {
    return page.locator('#Form_EditForm_ChildPages');
}

/**
 * The grid's rows as [title, state] pairs, top to bottom. state is the State column's own text,
 * plus ", Modified" when it carries the separate Modified label.
 */
export async function gridRows(grid: Locator): Promise<[string, string][]> {
    return grid.locator('tbody tr.ss-gridfield-item').evaluateAll((rows) =>
        rows.map((row) => {
            // A cell's own text nodes only: leaves out a status badge the SS6 CMS adds to the title
            // cell, and the Modified label inside the state cell.
            const ownText = (el: Element | null) =>
                el ? Array.from(el.childNodes).filter((n) => n.nodeType === 3).map((n) => n.textContent).join('').replace(/\s+/g, ' ').trim() : '';
            const stateCell = row.querySelector('td.gridfield-icon');
            const modified = stateCell?.querySelector('span.modified') ? ', Modified' : '';
            return [ownText(row.querySelector('td.col-Title')), ownText(stateCell) + modified];
        }),
    );
}

/** The ids of the form fields on the Main tab, in order (holders and GridFields). */
export async function mainTabFields(form: Locator): Promise<string[]> {
    return form.locator('#Root_Main > [id$="_Holder"], #Root_Main > fieldset.grid-field').evaluateAll((els) => els.map((e) => e.id));
}

/** Save the page ("Save" = write the draft) and wait for the AJAX save to come back 200. */
export async function saveDraft(page: Page): Promise<Request> {
    const posted = page.waitForRequest((r) => r.method() === 'POST' && /\/admin\/pages\/edit\/EditForm/.test(r.url()));
    await page.locator('button[name="action_save"]').click();
    const request = await posted;
    expect(['xhr', 'fetch']).toContain(request.resourceType());
    expect((await request.response())?.status(), 'save answered 200').toBe(200);
    await expect(page.locator('form#Form_EditForm input[name="Title"]')).toBeVisible();
    return request;
}

/** Render a page's front-end layout through the fixture endpoint (see NgBResetAdmin::layout()). */
export async function openLayout(page: Page, id: number): Promise<Locator> {
    await page.goto(`/admin/ng-reset/layout?id=${id}`);
    return page.locator('main.layout');
}
