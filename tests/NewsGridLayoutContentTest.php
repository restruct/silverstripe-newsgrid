<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridHolderController;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\NewsGridPageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use Restruct\SilverStripe\NewsGrid\Extensions\PaginatedItemsFallback;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * Issue #7: what the front-end Layout templates list and link, with AND without
 * filterablearchive (unlike NewsGridTemplatesTest, this runs either way).
 *
 * - The News section layout lists its published news items, newest first. Without filterablearchive,
 *   whose HolderControllerExtension provides $PaginatedItems, it used to list none (Silverstripe 5
 *   rendered one empty entry instead).
 * - A news item's layout links back to its News section. Without filterablearchive, whose
 *   ItemExtension provides $HolderPage, the link had no URL and no text.
 *
 * Rendered on the Live stage, as a visitor sees it.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridLayoutContentTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * A published section with two published items and one draft-only item, newest last.
     */
    private function makeSection(): array
    {
        $holder = NewsGridHolder::create(['Title' => 'News', 'URLSegment' => 'news']);
        $holder->write();
        $holder->publishSingle();
        $items = [];
        foreach ([['Older item', '2025-12-30', true], ['Newer item', '2026-01-02', true], ['Draft item', '2026-01-05', false]] as [$title, $date, $publish]) {
            $item = NewsGridPage::create(['Title' => $title, 'ParentID' => $holder->ID, 'Date' => $date, 'Content' => "<p>About $title.</p>"]);
            $item->write();
            if ($publish) {
                $item->publishSingle();
            }
            $items[$title] = $item;
        }

        return [$holder, $items];
    }

    private function renderLive(string $controllerClass, int $pageID, string $pageClass, array $getVars = []): string
    {
        return Versioned::withVersionedMode(function () use ($controllerClass, $pageID, $pageClass, $getVars) {
            Versioned::set_stage(Versioned::LIVE);
            $page = $pageClass::get()->byID($pageID);
            $controller = $controllerClass::create($page);
            $request = new HTTPRequest('GET', '/', $getVars);
            $request->setSession(new Session([]));
            $controller->setRequest($request);
            // Current, as during a real request (anything reading Controller::curr() sees this page)
            $controller->pushCurrent();
            try {
                return (string) $controller->renderWith(['type' => 'Layout', $pageClass]);
            } finally {
                $controller->popCurrent();
            }
        });
    }

    public function testNewsSectionLayoutListsItsPublishedItemsNewestFirst()
    {
        [$holder] = $this->makeSection();

        $html = $this->renderLive(NewsGridHolderController::class, $holder->ID, NewsGridHolder::class);

        preg_match_all('#<h4 class="mb-0">(.*?)</h4>#s', $html, $titles);
        $this->assertSame(['Newer item', 'Older item'], array_map('trim', $titles[1]));
        // One entry per item: no empty entry for a missing list
        $this->assertSame(2, substr_count($html, '<li>'));
    }

    public function testNewsSectionLayoutListsOnlyItsOwnItems()
    {
        [$holder] = $this->makeSection();
        $other = NewsGridHolder::create(['Title' => 'Other news', 'URLSegment' => 'other-news']);
        $other->write();
        $other->publishSingle();
        $foreign = NewsGridPage::create(['Title' => 'Other section item', 'ParentID' => $other->ID, 'Date' => '2026-01-03']);
        $foreign->write();
        $foreign->publishSingle();

        $html = $this->renderLive(NewsGridHolderController::class, $holder->ID, NewsGridHolder::class);

        $this->assertStringNotContainsString('Other section item', $html);
        preg_match_all('#<h4 class="mb-0">(.*?)</h4>#s', $html, $titles);
        $this->assertSame(['Newer item', 'Older item'], array_map('trim', $titles[1]));
    }

    public function testNewsItemLayoutLinksBackToItsNewsSection()
    {
        [$holder, $items] = $this->makeSection();

        $html = $this->renderLive(NewsGridPageController::class, $items['Older item']->ID, NewsGridPage::class);

        $this->assertMatchesRegularExpression(
            '#<div class="newsuplink"><a href="' . preg_quote($holder->Link(), '#') . '" [^>]*>&larr; News</a></div>#',
            $html
        );
    }

    public function testFallbackListIsAppliedOnlyWithoutFilterablearchive()
    {
        // Where filterablearchive is installed, its PaginatedItems() (with filters and pagination) must
        // be the one the template gets, so the fallback must not be applied next to it (two extensions
        // declaring one method name leave which one answers to the extension machinery).
        $withFilterable = ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\HolderControllerExtension');

        $this->assertSame(!$withFilterable, NewsGridHolderController::has_extension(PaginatedItemsFallback::class));
    }

    /**
     * Without filterablearchive the fallback list is paginated by NewsGridHolder.items_per_page, and
     * the section template renders the page links (a large archive must not render every item).
     */
    public function testWithoutFilterablearchiveTheListIsPaginatedWithPageLinks()
    {
        if (ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\HolderControllerExtension')) {
            $this->markTestSkipped('filterablearchive paginates by its own per-section ItemsPerPage');
        }
        NewsGridHolder::config()->set('items_per_page', 1);
        [$holder] = $this->makeSection();
        $titles = function (string $html): array {
            preg_match_all('#<h4 class="mb-0">(.*?)</h4>#s', $html, $m);
            return array_map('trim', $m[1]);
        };

        $page1 = $this->renderLive(NewsGridHolderController::class, $holder->ID, NewsGridHolder::class);
        $this->assertSame(['Newer item'], $titles($page1));
        $this->assertStringContainsString('<nav class="pagination_container">', $page1);
        // Page 2 is linked (?start=1), page 1 is the active one
        $this->assertMatchesRegularExpression('#<a class="page-link" href="[^"]*start=1" title="View page number 2">2</a>#', $page1);
        $this->assertMatchesRegularExpression('#<li class="page-item active">\s*<a class="page-link" href="[^"]*" title="View page number 1">1</a>#', $page1);

        $page2 = $this->renderLive(NewsGridHolderController::class, $holder->ID, NewsGridHolder::class, ['start' => 1]);
        $this->assertSame(['Older item'], $titles($page2));
        $this->assertMatchesRegularExpression('#<li class="page-item active">\s*<a class="page-link" href="[^"]*start=1" title="View page number 2">2</a>#', $page2);
    }

    public function testWithoutFilterablearchiveAPageLengthOfZeroListsEverythingWithoutPageLinks()
    {
        if (ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\HolderControllerExtension')) {
            $this->markTestSkipped('filterablearchive paginates by its own per-section ItemsPerPage');
        }
        NewsGridHolder::config()->set('items_per_page', 0);
        [$holder] = $this->makeSection();

        $html = $this->renderLive(NewsGridHolderController::class, $holder->ID, NewsGridHolder::class);

        $this->assertSame(2, substr_count($html, '<h4 class="mb-0">'));
        $this->assertStringNotContainsString('pagination_container', $html);
    }

    public function testItemsPerPageDefaultsToTwelve()
    {
        $this->assertSame(12, NewsGridHolder::config()->get('items_per_page'));
    }
}
