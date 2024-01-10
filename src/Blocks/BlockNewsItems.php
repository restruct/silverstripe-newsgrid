<?php

namespace Restruct\SilverStripe\NewsGrid;

// Only load if blockbase module installed (prevent 'Class not found' build-error)
if ( ! \SilverStripe\Core\ClassInfo::exists('Restruct\Silverstripe\BlockBase\Blocks\BlockContent') ) return;

use MultipleSelectionTag;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\SilverStripe\FilterableArchive\FilterPropRelation;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\TagField\StringTagField;
use SilverStripe\TagField\TagField;

/**
 * Base block class for common functionality (project specific blocks can subclass this, comparable to DataObject)
 */
class BlockNewsItems
extends BlockContent
{
//    private static $table_name = 'BlockNews';

    private static $icon = 'font-icon-globe';

    private static $description = 'Recent Newsitems';

    private static $has_heading = true;
    private static $has_introline = false;
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
            "Category",
            $availableCats
        )->setEmptyString('ANY/ALL');
        $fields->addFieldToTab("Root.Main", $categoriesField);

        // ExtraData functionality
        if($ExtraData = $this->getExtraData()) foreach ($fields->saveableFields() as $field){
            if(strpos($field->getName(), 'ExtraData_') === 0) {
                $field->setValue( $ExtraData[str_replace('ExtraData_', '', $field->getName())] ?? null );
            }
        }

        return $fields;
    }

    public function RecentNewsItems($limit = 3)
    {
        $items = NewsGridPage::get();

        if(isset($this->ExtraData['LimitByCatID'])) {
            $itemIDs = FilterPropRelation::get()->filter('CategoryID', $this->ExtraData['LimitByCatID'])->column('ItemID');
            $items = $items->filter('ID', count($itemIDs) ? $itemIDs : -1);
        }

        return $items->limit($limit);
    }
}
