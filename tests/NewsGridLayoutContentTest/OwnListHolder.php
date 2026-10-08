<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\NewsGridLayoutContentTest;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Dev\TestOnly;

/**
 * A project's News section that supplies its own PaginatedItems() on the record (e.g. a workaround
 * for issue #7 from before the fallback existed): the controller fallback must not hide it.
 */
class OwnListHolder extends NewsGridHolder implements TestOnly
{
    private static $table_name = 'NgOwnListHolder';

    public function PaginatedItems()
    {
        return NewsGridPage::get()->filter(['ParentID' => $this->ID, 'Title' => 'Older item']);
    }
}
