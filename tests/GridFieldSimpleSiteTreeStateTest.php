<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\Fields\GridFieldSimpleSiteTreeState;
use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Security\Member;

/**
 * The simplified Lumberjack state column: status only, no publish date and time.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class GridFieldSimpleSiteTreeStateTest extends SapphireTest
{
    protected $usesDatabase = true;

    private function stateOf($record, string $column = 'State')
    {
        $component = new GridFieldSimpleSiteTreeState();

        return $component->getColumnContent(GridField::create('ChildPages'), $record, $column);
    }

    private function makeItem(): NewsGridPage
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $item = NewsGridPage::create(['Title' => 'Item', 'ParentID' => $holder->ID]);
        $item->write();

        return $item;
    }

    public function testDraftItemShowsTheDraftLabel()
    {
        $html = $this->stateOf($this->makeItem());

        $this->assertStringContainsString('font-icon-pencil', $html);
        $this->assertStringContainsString('Draft', $html);
    }

    public function testPublishedItemShowsAPublishedLabelWithoutADate()
    {
        $item = $this->makeItem();
        $item->publishRecursive();

        $html = $this->stateOf($item);

        $this->assertStringContainsString('font-icon-check-mark-circle', $html);
        $this->assertStringContainsString('Published', $html);
        // The _t() default here is itself an entity key; it must resolve to the translated label
        $this->assertStringNotContainsString('ContentController.PUBLISHED', $html);
        $this->assertStringNotContainsString('Modified', $html);
        // Lumberjack's own column says "Published on {date}"; this one deliberately does not
        $this->assertStringNotContainsString(' on ', strip_tags($html));
    }

    public function testPublishedItemWithDraftChangesIsMarkedModified()
    {
        $item = $this->makeItem();
        $item->publishRecursive();
        $item->Title = 'Item, edited';
        $item->write();

        $html = $this->stateOf($item);

        $this->assertStringContainsString('font-icon-check-mark-circle', $html);
        $this->assertStringContainsString('<span class="modified">Modified</span>', $html);
    }

    public function testOtherColumnsAndUnversionedRecordsAreLeftAlone()
    {
        $this->assertNull($this->stateOf($this->makeItem(), 'Title'));
        // A record without isPublished() gets no state at all
        $this->assertNull($this->stateOf(Member::create()));
    }
}
