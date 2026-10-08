<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;

/**
 * The real BlockNewsItems (blockbase installed) builds its CMS fields with and without
 * filterablearchive. Its category filter reads the News sections' Categories relation, which only
 * filterablearchive adds, so without it the edit form used to throw ("the method 'Categories' does
 * not exist"). Skipped where blockbase is not installed: the class is not declared there.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class BlockNewsItemsCmsFieldsTest extends SapphireTest
{
    protected $usesDatabase = true;

    private const BLOCK = 'Restruct\SilverStripe\NewsGrid\BlockNewsItems';

    protected function setUp(): void
    {
        parent::setUp();
        if (!ClassInfo::exists('Restruct\Silverstripe\BlockBase\Blocks\BlockContent')) {
            $this->markTestSkipped('blockbase is not installed');
        }
    }

    public function testCmsFieldsBuildAndOfferTheCategoryFilterOnlyWithFilterablearchive()
    {
        $class = self::BLOCK;
        $fields = $class::create()->getCMSFields();

        $this->assertNotNull($fields->dataFieldByName('IntroLine'));
        $withFilterable = ClassInfo::exists('Restruct\SilverStripe\FilterableArchive\Extensions\HolderExtension');
        $this->assertSame($withFilterable, $fields->dataFieldByName('ExtraData_LimitByCatID') !== null);
    }
}
