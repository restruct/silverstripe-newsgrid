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
        // Decided for 3.1.0: changed to 'd MMM y', so the expectation is now '2 Jan 2026' (was '2 1 2026').
        //$this->assertSame('2 1 2026', $this->makeItem('2026-01-02')->formattedPublishDate());
        $this->assertSame('2 Jan 2026', $this->makeItem('2026-01-02')->formattedPublishDate());
    }

    public function testFormattedPublishDateShowsMonthNameAndCalendarYear()
    {
        // 'd MMM y' since 3.1.0: abbreviated month name and calendar year. 30 December 2024 falls in
        // the first week of 2025, so a week-year pattern (Y) would print 2025 here, and a month-number
        // pattern (M) would print 12.
        $this->assertSame('2 Jan 2026', $this->makeItem('2026-01-02')->formattedPublishDate());
        $this->assertSame('30 Dec 2024', $this->makeItem('2024-12-30')->formattedPublishDate());
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
