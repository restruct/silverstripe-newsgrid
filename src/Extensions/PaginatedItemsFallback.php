<?php

namespace Restruct\SilverStripe\NewsGrid\Extensions;

use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Core\Extension;

# PaginatedList is NOT imported: it moved from SilverStripe\ORM (5) to SilverStripe\Model\List (6)
# with no alias left behind, so the class name is resolved per major (as filterablearchive does).

/**
 * $PaginatedItems for the News section page when restruct/silverstripe-filterablearchive is NOT
 * installed (issue #7).
 *
 * The section's Layout template loops over $PaginatedItems, which filterablearchive's
 * HolderControllerExtension provides; without it the page listed no news items. _config/config.yml
 * applies this extension to NewsGridHolderController only when that class does not exist, so where
 * filterablearchive is installed its own method (with its date/category/tag filters and pagination)
 * is the one in use, and this one is never added.
 *
 * Same list as filterablearchive's unfiltered one: the section's own news items, newest first, in
 * the current reading mode (so Live for visitors, draft in a CMS preview), paginated over the
 * request's ?start= by the section's items_per_page config (NewsGridHolder default 12, a subclass
 * may set its own; 0 = every item on one page, as
 * filterablearchive's ItemsPerPage of 0). The section template renders the page links
 * (Includes/NewsGridPagination.ss) when there is more than one page.
 */
class PaginatedItemsFallback extends Extension
{
    /**
     * @return \SilverStripe\ORM\PaginatedList|\SilverStripe\Model\List\PaginatedList
     */
    public function PaginatedItems()
    {
        $items = NewsGridPage::get()
            ->filter('ParentID', $this->getOwner()->data()->ID)
            ->sort('Date', 'DESC');

        $listClass = class_exists('SilverStripe\\Model\\List\\PaginatedList')
            ? 'SilverStripe\\Model\\List\\PaginatedList'
            : 'SilverStripe\\ORM\\PaginatedList';
        $list = $listClass::create($items, $this->getOwner()->getRequest());
        # A large archive must not render hundreds of items on one page, so paginate by config. Read
        # from the section record's own class, so a NewsGridHolder subclass can set its own page length.
//        $perPage = (int) NewsGridHolder::config()->get('items_per_page');
        $perPage = (int) $this->getOwner()->data()->config()->get('items_per_page');
        # 0 (or less) = no pagination: one page holding everything (a page length of 0 would break
        # the page count, hence at least 1)
        $list->setPageLength($perPage > 0 ? $perPage : max(1, $items->count()));

        return $list;
    }
}
