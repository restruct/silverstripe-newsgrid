<?php

namespace Restruct\SilverStripe\NewsGrid\Extensions {

    use Restruct\SilverStripe\NewsGrid\NewsGridPage;
    use SilverStripe\Core\Extension;

    class FilterableCategoryExtension extends Extension
    {

        private static $many_many = [
            "Items" => NewsGridPage::class,
        ];

    }
}