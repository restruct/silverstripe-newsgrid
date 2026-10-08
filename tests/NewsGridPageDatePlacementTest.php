<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\Tests\NewsGridPageDatePlacementTest\DateOnSettingsExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Issue #8 follow-up: NewsGridPage places the Date field before Content, but must not override a
 * project that placed it elsewhere (extensions run inside parent::getCMSFields(), before the module
 * places its field). NoContentExtension is covered by NewsGridPageDateNoContentTest.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridPageDatePlacementTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        NewsGridPage::class => [DateOnSettingsExtension::class],
    ];

    public function testDateAProjectPutOnSettingsStaysThere()
    {
        $fields = NewsGridPage::create()->getCMSFields();

        $this->assertNotNull($fields->fieldByName('Root.Settings.Date'), 'Date stays on Settings');
        $this->assertNull($fields->fieldByName('Root.Main.Date'), 'not moved to Main');
        $this->assertNotContains('Date', self::topLevelNames($fields));
    }

    /** Names of the form's top-level fields (normally just the Root tabset). */
    public static function topLevelNames(FieldList $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            $names[] = $field->getName();
        }

        return $names;
    }
}
