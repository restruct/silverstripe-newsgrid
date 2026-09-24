<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\BlockNewsItemsTest;

use SilverStripe\Dev\TestOnly;

/**
 * Stands in for blockbase's BlockContent as the parent of BlockNewsItems' source under test.
 *
 * Only the two fields BlockNewsItems' public methods read are provided. Deliberately NOT named
 * Restruct\Silverstripe\BlockBase\Blocks\BlockContent: declaring that name would make the real
 * BlockNewsItems loadable for the rest of the test run, and ModuleConfigTest checks it is not.
 */
class BlockContentStub implements TestOnly
{
    # blockbase keeps per-block settings in ExtraData; BlockNewsItems reads LimitByCatID from it
    public $ExtraData = [];

    # BlockNewsItems uses the IntroLine field as the label of its "all news" link
    public $IntroLine = null;
}
