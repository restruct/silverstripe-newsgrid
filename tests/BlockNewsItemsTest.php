<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\Tests\BlockNewsItemsTest\BlockContentStub;
use RuntimeException;
use SilverStripe\Dev\SapphireTest;

/**
 * BlockNewsItems' public methods, RecentNewsItems() and NewsSectionLink().
 *
 * BlockNewsItems extends blockbase's BlockContent, and blockbase (1.0.8, dev-main) requires
 * Silverstripe 4, so on the majors this module supports the class is never declared (its file
 * returns early; ModuleConfigTest checks that). To still run the methods' real code, this test
 * loads src/Blocks/BlockNewsItems.php, drops the file-level guard, renames the class and points
 * it at BlockContentStub, then evaluates it. The method bodies are the shipped source, so a change
 * to them shows up here. A source layout this test does not recognise fails loudly rather than
 * testing something else.
 *
 * Not covered: the LimitByCatID branch of RecentNewsItems(), which needs filterablearchive's
 * FilterPropRelation.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class BlockNewsItemsTest extends SapphireTest
{
    protected $usesDatabase = true;

    private const CLASS_UNDER_TEST = 'Restruct\SilverStripe\NewsGrid\BlockNewsItems_SourceUnderTest';

    /**
     * A BlockNewsItems built from the shipped source, with BlockContentStub as its parent.
     */
    private function makeBlock(): BlockContentStub
    {
        if (!class_exists(self::CLASS_UNDER_TEST, false)) {
            $source = file_get_contents(__DIR__ . '/../src/Blocks/BlockNewsItems.php');
            $replacements = [
                // the opening tag: eval() takes code, not a file
                '/^<\?php/' => '',
                // the file-level guard that returns when blockbase is not installed
                '/if \(\s*!\s*ClassInfo::exists\([^)]*BlockContent\'\)\)\s*\{\s*return;\s*\}/' => '',
                // the declaration: a name nothing else uses, and the stub as parent
                '/class BlockNewsItems extends BlockContent\b/' => 'class BlockNewsItems_SourceUnderTest extends \\' . BlockContentStub::class,
            ];
            foreach ($replacements as $pattern => $replacement) {
                $source = preg_replace($pattern, $replacement, $source, -1, $count);
                if ($count !== 1) {
                    throw new RuntimeException("BlockNewsItems.php no longer matches $pattern; update this test");
                }
            }
            eval($source);
        }
        $class = self::CLASS_UNDER_TEST;

        return new $class();
    }

    private function makeItem(NewsGridHolder $holder, string $title, string $date): NewsGridPage
    {
        $item = NewsGridPage::create(['Title' => $title, 'ParentID' => $holder->ID, 'Date' => $date]);
        $item->write();

        return $item;
    }

    public function testRecentNewsItemsReturnsTheThreeNewest()
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $this->makeItem($holder, 'Oldest', '2025-01-01');
        $this->makeItem($holder, 'Newest', '2026-03-01');
        $this->makeItem($holder, 'Middle', '2026-01-01');
        $this->makeItem($holder, 'Second', '2026-02-01');

        $items = $this->makeBlock()->RecentNewsItems();

        $this->assertSame(['Newest', 'Second', 'Middle'], $items->column('Title'));
    }

    public function testRecentNewsItemsHonoursTheLimit()
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $this->makeItem($holder, 'Older', '2025-01-01');
        $this->makeItem($holder, 'Newer', '2026-01-01');

        $this->assertSame(['Newer'], $this->makeBlock()->RecentNewsItems(1)->column('Title'));
    }

    public function testNewsSectionLinkIsNullWithoutALabel()
    {
        NewsGridHolder::create(['Title' => 'News'])->write();

        $this->assertNull($this->makeBlock()->NewsSectionLink());
    }

    public function testNewsSectionLinkIsTheFirstNewsSectionLabelled()
    {
        $first = NewsGridHolder::create(['Title' => 'First news']);
        $first->write();
        NewsGridHolder::create(['Title' => 'Second news'])->write();

        $block = $this->makeBlock();
        $block->IntroLine = 'All news';
        $link = $block->NewsSectionLink();

        $this->assertInstanceOf(NewsGridHolder::class, $link);
        $this->assertSame($first->ID, $link->ID);
        $this->assertSame('All news', $link->LinkLabel);
    }
}
