<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\NewsGridPageDatePlacementTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;

/**
 * A project extension that puts the news item's Date on the Settings tab (runs inside
 * SiteTree::getCMSFields(), so before NewsGridPage::getCMSFields() places its own).
 */
class DateOnSettingsExtension extends Extension implements TestOnly
{
    public function updateCMSFields(FieldList $fields)
    {
        # Silverstripe 6 scaffolded one on Main, Silverstripe 5 has none: either way it ends on Settings
        $date = $fields->dataFieldByName('Date') ?: DateField::create('Date');
        $fields->removeByName('Date');
        $fields->addFieldToTab('Root.Settings', $date);
    }
}
