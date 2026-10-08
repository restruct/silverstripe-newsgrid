<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\NewsGridPageDatePlacementTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;

/**
 * A project extension that removes the Content field from news items.
 */
class NoContentExtension extends Extension implements TestOnly
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('Content');
    }
}
