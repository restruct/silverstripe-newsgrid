<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\NewsGridLayoutContentTest;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use SilverStripe\Dev\TestOnly;

/**
 * A project's own News section type with its own page length: items_per_page set on the subclass
 * must be the one the fallback list uses.
 */
class PagedHolder extends NewsGridHolder implements TestOnly
{
    private static $table_name = 'NgPagedHolder';

    private static $items_per_page = 1;
}
