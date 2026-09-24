<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\Tests\NewsGridTemplateGuardTest\DateFieldExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * NewsGridPage's template-facing methods, formattedPublishDate() and DateFieldComment().
 *
 * DateFieldComment() calls getDateField(), which filterablearchive's ItemExtension provides; the
 * TestOnly DateFieldExtension stands in for it, so this runs with or without filterablearchive.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridPageApiTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        NewsGridPage::class => [DateFieldExtension::class],
    ];

    protected function tearDown(): void
    {
        DBDatetime::clear_mock_now();
        parent::tearDown();
    }

    private function makeItem(string $date): NewsGridPage
    {
        $holder = NewsGridHolder::create(['Title' => 'News']);
        $holder->write();
        $item = NewsGridPage::create(['Title' => 'Item', 'ParentID' => $holder->ID, 'Date' => $date]);
        $item->write();

        return $item;
    }

    public function testFormattedPublishDateUsesTheCldrPattern()
    {
        // Pins the output as documented in the README: 'd M Y' is a CLDR pattern (day, month number,
        // week-year), not PHP date(). Whether to change it to 'd MMM y' is an open release decision;
        // if it changes, this expectation changes with it.
        $this->assertSame('2 1 2026', $this->makeItem('2026-01-02')->formattedPublishDate());
    }

    public function testDateFieldCommentWithinTheFirstHourOfTheDay()
    {
        // The item has a date, not a time, so "today and at most 60 minutes ago" means the first hour
        DBDatetime::set_mock_now('2026-01-02 00:30:00');

        $comment = $this->makeItem('2026-01-02')->DateFieldComment();

        $this->assertMatchesRegularExpression('/^\(30 min.* ago\)$/', (string) $comment);
    }

    public function testDateFieldCommentIsNullLaterThatDay()
    {
        DBDatetime::set_mock_now('2026-01-02 12:00:00');

        $this->assertNull($this->makeItem('2026-01-02')->DateFieldComment());
    }

    public function testDateFieldCommentIsNullForAnotherDay()
    {
        DBDatetime::set_mock_now('2026-01-03 00:30:00');

        $this->assertNull($this->makeItem('2026-01-02')->DateFieldComment());
    }
}
