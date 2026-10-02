<?php

namespace Restruct\NgBrowser;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\View\Parsers\URLSegmentFilter;

/**
 * BROWSER-TEST FIXTURE ONLY - lets a spec start from a known News section:
 * GET /admin/ng-reset/reseed?title=... answers {"id": section ID, "link": its URL, "items": {title: ID}}.
 *
 * And renders a page's front-end layout: GET /admin/ng-reset/layout?id=... The scratch host has no
 * theme, so there is no main Page.ss and the page's own URL cannot render; this renders the
 * module's Layout template for the page type (templates/Restruct/SilverStripe/NewsGrid/Layout/)
 * in the scope of the page's own controller, inside a bare HTML document.
 *
 * The section (published) holds three news items in the three states its grid shows:
 *   "Published item"  2025-12-30, published
 *   "Modified item"   2026-01-02, published, then changed on draft
 *   "Draft item"      2026-01-05, never published
 *
 * A LeftAndMain because the admin routes those by url_segment with no YAML (the fixtures are copied
 * into app/src/). LeftAndMain's own access check applies, so only the logged-in admin can call it.
 * Never loaded by a real install (tests/browser/ carries a _manifest_exclude marker).
 */
class NgBResetAdmin extends LeftAndMain
{
    private static $url_segment = 'ng-reset';

    private static $menu_title = 'Newsgrid browser reset';

    private static $allowed_actions = ['reseed', 'layout'];

    public function layout(HTTPRequest $request): HTTPResponse
    {
        $page = SiteTree::get()->byID((int) $request->getVar('id'));
        if (!$page) {
            return $this->httpError(404, 'no such page');
        }
        $controller = ModelAsController::controller_for($page);
        $controller->setRequest($request);
        $controller->pushCurrent();
        try {
            $layout = (string) $controller->renderWith(['type' => 'Layout', get_class($page)]);
        } finally {
            $controller->popCurrent();
        }

        return HTTPResponse::create(
            '<!doctype html><html lang="en"><head><meta charset="utf-8"><link rel="icon" href="data:,">'
            . '<title>' . htmlspecialchars((string) $page->Title, ENT_QUOTES) . '</title></head>'
            . '<body><main class="layout">' . $layout . '</main></body></html>'
        );
    }

    public function reseed(HTTPRequest $request): HTTPResponse
    {
        $title = (string) $request->getVar('title');
        if ($title === '') {
            return $this->httpError(400, 'title is required');
        }
        $segment = 'ng-' . URLSegmentFilter::create()->filter($title);

        foreach (NewsGridHolder::get()->filter('URLSegment', $segment) as $old) {
            foreach (NewsGridPage::get()->filter('ParentID', $old->ID) as $item) {
                $item->doArchive();
            }
            $old->doArchive();
        }

        $holder = NewsGridHolder::create(['Title' => $title, 'URLSegment' => $segment, 'Content' => '<p>All the news.</p>']);
        $holder->write();
        $holder->publishSingle();

        $ids = [];
        $seeds = [
            ['Published item', '2025-12-30', 'publish'],
            ['Modified item', '2026-01-02', 'modify'],
            ['Draft item', '2026-01-05', 'draft'],
        ];
        foreach ($seeds as [$itemTitle, $date, $state]) {
            $item = NewsGridPage::create([
                'Title' => $itemTitle,
                'URLSegment' => $segment . '-' . URLSegmentFilter::create()->filter($itemTitle),
                'ParentID' => $holder->ID,
                'Date' => $date,
                'Content' => '<p>About ' . $itemTitle . '.</p>',
            ]);
            $item->write();
            if ($state !== 'draft') {
                $item->publishSingle();
            }
            if ($state === 'modify') {
                $item->Content = '<p>About ' . $itemTitle . ', edited.</p>';
                $item->write();
            }
            $ids[$itemTitle] = $item->ID;
        }

        return HTTPResponse::create(json_encode(['id' => $holder->ID, 'link' => $holder->Link(), 'items' => $ids]))
            ->addHeader('Content-Type', 'application/json');
    }
}
