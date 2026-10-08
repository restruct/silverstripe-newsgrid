<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DateField;

/**
 * Issue #8: a news item's Date (its sort key) must be editable in the CMS on both majors, with and
 * without filterablearchive.
 *
 * Silverstripe 6 scaffolds page fields from $db, so it showed a Date field (after Content);
 * Silverstripe 5's SiteTree::getCMSFields() does not scaffold, so without filterablearchive (whose
 * ItemExtension adds one, and only when its date archive is active) there was none. Expected now:
 * exactly one Date field, on the Main tab, directly before Content, everywhere.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridPageDateFieldTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testDateFieldIsOnTheMainTabOnceDirectlyBeforeContent()
    {
        $this->assertDateFieldOnceBeforeContent([]);
    }

    public function testFilterablearchivesOwnDateFieldIsNotDuplicated()
    {
        // With its date archive active, filterablearchive's ItemExtension adds its own Date field
        if (!ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\ItemExtension')) {
            $this->markTestSkipped('filterablearchive is not installed');
        }
        $this->assertDateFieldOnceBeforeContent(['DateFilterEnabled' => true]);
    }

    private function assertDateFieldOnceBeforeContent(array $holderData): void
    {
        $holder = NewsGridHolder::create(['Title' => 'News'] + $holderData);
        $holder->write();
        $item = NewsGridPage::create(['Title' => 'Item', 'ParentID' => $holder->ID, 'Date' => '2025-12-30']);
        $item->write();

        $fields = $item->getCMSFields();

        $date = $fields->dataFieldByName('Date');
        $this->assertInstanceOf(DateField::class, $date);

        $names = [];
        foreach ($fields->fieldByName('Root.Main')->FieldList()->flattenFields() as $field) {
            $names[] = $field->getName();
        }
        $this->assertSame(1, count(array_keys($names, 'Date')), 'one Date field on the Main tab');
        $this->assertSame(array_search('Content', $names) - 1, array_search('Date', $names), 'Date directly before Content');
    }
}
