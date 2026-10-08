<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use Restruct\SilverStripe\NewsGrid\Tests\NewsGridPageDatePlacementTest\NoContentExtension;
use SilverStripe\Dev\SapphireTest;

/**
 * Issue #8 follow-up: with Content removed by a project, the module's Date field must still land on
 * the Main tab, not outside the Root tabset (insertBefore() appends to the top level when its target
 * is missing).
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridPageDateNoContentTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        NewsGridPage::class => [NoContentExtension::class],
    ];

    public function testDateStaysInTheTabsetWithoutContent()
    {
        $fields = NewsGridPage::create()->getCMSFields();

        $this->assertNull($fields->dataFieldByName('Content'), 'precondition: Content removed');
        $this->assertNotNull($fields->fieldByName('Root.Main.Date'), 'Date on the Main tab');
        $this->assertNotContains('Date', NewsGridPageDatePlacementTest::topLevelNames($fields));
    }
}
