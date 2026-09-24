<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Module-level wiring: YAML that has to land on the right class per Silverstripe major, and the
 * optional-dependency guard on BlockNewsItems.
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class ModuleConfigTest extends SapphireTest
{
    public function testLegacyClassNamesAreRemappedOnTheClassThisMajorReads()
    {
        // SS5 reads classname_value_remapping from DatabaseAdmin, SS6 from DbBuild (DatabaseAdmin is gone).
        // Config set on a class nothing reads is silently ignored, so check the one that is read.
        $reader = class_exists('SilverStripe\Dev\Command\DbBuild')
            ? 'SilverStripe\Dev\Command\DbBuild'
            : 'SilverStripe\ORM\DatabaseAdmin';
        $remapping = Config::inst()->get($reader, 'classname_value_remapping');

        $this->assertSame(NewsGridHolder::class, $remapping['NewsGridHolder'] ?? null);
        $this->assertSame(NewsGridPage::class, $remapping['NewsGridPage'] ?? null);
    }

    public function testCmsStylesheetIsRegistered()
    {
        $this->assertContains(
            'restruct/silverstripe-newsgrid:client/css/newsgridpages.css',
            LeftAndMain::config()->get('extra_requirements_css')
        );
    }

    public function testBlockIsNotDeclaredWithoutBlockbase()
    {
        // BlockNewsItems extends a blockbase class; without blockbase its file returns before the
        // class declaration instead of fataling the manifest build.
        if (ClassInfo::exists('Restruct\Silverstripe\BlockBase\Blocks\BlockContent')) {
            $this->markTestSkipped('blockbase is installed');
        }
        $this->assertFalse(class_exists('Restruct\SilverStripe\NewsGrid\BlockNewsItems'));
    }
}
