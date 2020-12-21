<?php

namespace Restruct\SilverStripe\NewsGrid\Extensions {

    use Restruct\SilverStripe\NewsGrid\NewsGridPage;
    use SilverStripe\ORM\DataExtension;
    use SilverStripe\ORM\DataObject;

    class FilterableCategoryExtension extends DataExtension
    {

        private static $many_many = [
            "Items" => NewsGridPage::class,
        ];

    }
}