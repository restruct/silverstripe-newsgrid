<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\Tests\NewsGridTemplateGuardTest\DateFieldExtension;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;

/**
 * The FilterableProperties includes must RENDER on a news item when filterablearchive is present.
 *
 * Regression for the 3.0.x guard: it tested $hasMethod('FilterDropdown'), which only a holder has, so
 * in an item's scope the properties never showed. The guard now tests getDateField(), which
 * filterablearchive's ItemExtension adds to items. NewsGridTemplatesTest covers the other side (no
 * filterablearchive: the include is skipped and nothing throws).
 *
 * filterablearchive itself is not needed: a TestOnly extension provides getDateField(), and a stub
 * theme in tests/NewsGridTemplateGuardTest/themes/stub provides a marker FilterableProperties include.
 * The stub theme sits in front of the theme stack, so this runs the same with or without
 * filterablearchive installed and never self-skips.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridTemplateGuardTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        NewsGridPage::class => [DateFieldExtension::class],
    ];

    /**
     * Render inside the stub theme (tests/NewsGridTemplateGuardTest/themes/stub), in front of the
     * default theme stack. SapphireTest::useTestTheme() nests Config but does not restore the active
     * themes (a static), so they are put back here for the tests that run after this one.
     */
    private function renderInStubTheme(callable $render): string
    {
        # The module's path relative to the project (vendor/restruct/silverstripe-newsgrid), so this
        # works from any host project; useTestTheme() treats a leading "/" as the project root.
        $modulePath = ModuleLoader::getModule('restruct/silverstripe-newsgrid')->getRelativePath();
        $previousThemes = SSViewer::get_themes();
        $html = '';
        try {
            $this->useTestTheme('/' . $modulePath . '/tests/NewsGridTemplateGuardTest', 'stub', function () use ($render, &$html) {
                $html = (string) $render();
            });
        } finally {
            SSViewer::set_themes($previousThemes);
        }

        return $html;
    }

    private function makeItem(): NewsGridPage
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $item = NewsGridPage::create([
            'Title' => 'Guarded item',
            'ParentID' => $holder->ID,
            'Date' => '2026-01-02',
        ]);
        $item->write();

        return $item;
    }

    public function testItemHasTheMethodTheGuardsTestFor()
    {
        // Control: without this the render tests below would pass or fail for the wrong reason
        $this->assertTrue($this->makeItem()->hasMethod('getDateField'));
    }

    public function testNewsItemTileRendersTheFilterableProperties()
    {
        $item = $this->makeItem();
        $html = $this->renderInStubTheme(fn () => $item->renderWith('Includes/NewsItemTile'));

        $this->assertStringContainsString('<span class="stub-filterable-properties">2026</span>', $html);
    }

    public function testNewsItemLayoutRendersTheFilterableProperties()
    {
        // Rendered on the item itself, not through a NewsGridPageController: a controller caches its
        // failover's methods per class (CustomMethods::$extra_methods), so after this test the stand-in
        // getDateField() would still be reported by every later NewsGridPageController, and
        // NewsGridTemplatesTest (which renders through one) would take the guarded branch.
        $item = $this->makeItem();
        $html = $this->renderInStubTheme(fn () => $item->renderWith(['type' => 'Layout', NewsGridPage::class]));

        $this->assertStringContainsString('<span class="stub-filterable-properties">2026</span>', $html);
        $this->assertStringContainsString('<h1>Guarded item</h1>', $html);
    }
}
