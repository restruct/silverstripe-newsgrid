<?php

namespace Restruct\NewsGrid\Extensions {

    use Restruct\FilterableArchive\FilterableCategory;
    use Restruct\FilterableArchive\FilterableTag;
    use Restruct\NewsGrid\NewsGridPage;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\HTTPRequest;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\ListboxField;
    use SilverStripe\ORM\DataExtension;
    use SilverStripe\ORM\DataObject;
    use SilverStripe\TagField\TagField;

    class FilterableCategoryExtension extends DataExtension
    {

        private static $many_many = [
            "Items" => NewsGridPage::class,
        ];

    }
}