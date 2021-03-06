<?php

namespace Restruct\SilverStripe\NewsGrid\Extensions {

    use SilverStripe\CMS\Controllers\CMSPagesController;
    use SilverStripe\Control\Controller;
    use SilverStripe\Lumberjack\Model\Lumberjack;

    class CustomLumberjack extends Lumberjack
    {

        /**
         * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
         *
         * @return bool
         */
        protected function shouldFilter()
        {
            $controller = Controller::curr();

            //return get_class($controller) === CMSPagesController::class
            return Controller::curr() instanceof CMSPagesController
                // DON'T filter listview, after all, that's what its for (to show large sets of pages)
                && in_array($controller->getAction(), [ "treeview", "getsubtree" ]);
        }

    }
}