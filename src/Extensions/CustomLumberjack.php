<?php

namespace Restruct\SilverStripe\NewsGrid\Extensions {

    use Override;
    use SilverStripe\CMS\Controllers\CMSMain;
    use SilverStripe\Control\Controller;
    use SilverStripe\Lumberjack\Model\Lumberjack;

    class CustomLumberjack extends Lumberjack
    {

        /**
         * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
         *
         * Override to exclude 'listview' action - we want news items to show in list view
         * but not in tree view to avoid cluttering the site tree.
         *
         * @return bool
         */
        #[Override]
        protected function shouldFilter()
        {
            $controller = Controller::curr();

            // Only filter in CMS Main controller (pages section)
            if (!($controller instanceof CMSMain)) {
                return false;
            }

            // Filter in tree view and getsubtree, but NOT in listview
            // (listview is meant to show large sets of pages)
            return in_array($controller->getAction(), ['index', 'show', 'treeview', 'getsubtree']);
        }

    }
}