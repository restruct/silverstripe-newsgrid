<?php

namespace Restruct\SilverStripe\NewsGrid;

use SilverStripe\Core\ClassInfo;

// Only load if blockbase module installed (prevent 'Class not found' build-error)
if (! ClassInfo::exists('Restruct\Silverstripe\BlockBase\Blocks\BlockContent')) {
    return;
}
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\SilverStripe\FilterableArchive\FilterPropRelation;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;

/**
 * Base block class for common functionality (project specific blocks can subclass this, comparable to DataObject)
 */
class BlockNewsItems extends BlockContent
{
//    private static $table_name = 'BlockNews';

    private static $icon = 'font-icon-globe';

    private static $description = 'Recent Newsitems';

    private static $has_heading = true;

    private static $has_introline = true;

    private static $has_image = false;

    private static $has_content = false;

    private static $has_bg_image = false;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Style');
        $fields->push(HiddenField::create('Style')->setValue('single-col-wide'));

        // Optionally limit items by a specific Category
        // Would be nice to allow multiple but ListboxField is STILL not working as react component so cannot
        // be used inline in a block and StringTagField STILL causes error
        $availableCats = NewsGridHolder::get()->relation('Categories');
        $categoriesField = DropdownField::create(
            'ExtraData_LimitByCatID',
            "Filter by category",
            $availableCats
        )->setEmptyString('ANY/ALL');
        $fields->addFieldToTab("Root.Main", $categoriesField, 'IntroLine');

        $fields->replaceField('IntroLine',
            TextField::create('IntroLine', 'Label for ‘All news’ button/link')
                ->setDescription('Links to first News-section (leave empty for no button/link)')
        );

        return $fields;
    }

    public function RecentNewsItems($limit = 3)
    {
        $items = NewsGridPage::get();

        if(!empty($this->ExtraData['LimitByCatID'])) {
            $itemIDs = FilterPropRelation::get()->filter('CategoryID', $this->ExtraData['LimitByCatID'])->column('ItemID');
            $items = $items->filter('ID', count($itemIDs) > 0 ? $itemIDs : -1);
        }

        return $items->limit($limit);
    }

    public function NewsSectionLink()
    {
        if (!$this->IntroLine) {
            return null;
        }

        $NewsSection = NewsGridHolder::get()->first();
        $NewsSection->LinkLabel = $this->IntroLine;

        return $NewsSection;
    }
}
