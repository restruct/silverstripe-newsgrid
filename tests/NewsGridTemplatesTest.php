<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridHolderController;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\NewsGridPageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;

/**
 * The module's templates must render when the optional modules are NOT installed.
 *
 * FilterableProperties and friends ship with restruct/silverstripe-filterablearchive; an include
 * of a template that does not exist throws on render, so every such include is guarded. This
 * suite runs without filterablearchive, which is exactly the case the guards are for.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridTemplatesTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\ItemExtension')) {
            $this->markTestSkipped('filterablearchive is installed; these tests cover the case without it');
        }
    }

    private function makeHolderWithItem(): array
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $item = NewsGridPage::create([
            'Title' => 'Item one',
            'Content' => '<p>First paragraph of item one.</p>',
            'ParentID' => $holder->ID,
            'Date' => '2026-01-02',
        ]);
        $item->write();

        return [$holder, $item];
    }

    public function testNewsItemLayoutRenders()
    {
        [, $item] = $this->makeHolderWithItem();
        $controller = NewsGridPageController::create($item);
        $controller->setRequest(new HTTPRequest('GET', '/'));

        $html = (string) $controller->renderWith(['type' => 'Layout', NewsGridPage::class]);

        $this->assertStringContainsString('<h1>Item one</h1>', $html);
        $this->assertStringContainsString('First paragraph of item one.', $html);
    }

    public function testNewsSectionLayoutRenders()
    {
        [$holder] = $this->makeHolderWithItem();
        $controller = NewsGridHolderController::create($holder);
        $controller->setRequest(new HTTPRequest('GET', '/'));

        $html = (string) $controller->renderWith(['type' => 'Layout', NewsGridHolder::class]);

        $this->assertStringContainsString('<h1 class="display-4">News</h1>', $html);
    }

    public function testNewsItemTileRenders()
    {
        // Rendered by BlockNewsItems (blockbase) for each recent item
        [, $item] = $this->makeHolderWithItem();

        $html = (string) $item->renderWith('Includes/NewsItemTile');

        $this->assertStringContainsString('Item one', $html);
        $this->assertStringContainsString('newsitem-tile-holder', $html);
    }
}
