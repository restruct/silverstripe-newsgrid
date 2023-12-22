<?php

namespace Restruct\SilverStripe\NewsGrid;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

class NewsGridPage
    extends \Page
{
    private static $table_name = 'NewsGridPage';
    private static $singular_name = 'NewsItem';
    private static $plural_name = 'NewsItems';
    private static $description = 'Create a news item';

    private static $can_be_root = false;
    private static $show_in_sitetree = false; //@TODO: fix this... (why is the config not being applied?)
    //private static $allowed_children = "none";

    private static $icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

    // For filterableArchive:
//    private static $field_for_date_filter = 'Date';

    private  static $default_sort = "Date DESC";

    private static $db = [
        'Date'        => 'Date',
        'NoAutoImage' => 'Boolean',
    ];

    private   static $searchable_fields = [
        'Title' => [ 'title' => 'Title' ],
        'Date'  => [ 'title' => 'Date' ],
        //'LeadsID' => array('title' => 'Leads')
    ];

    public function formattedPublishDate()
    {
        //return $this->obj('Date')->Format('Y-m-d');
        return $this->obj('Date')->Format('d M Y');
    }

    public function populateDefaults()
    {
        $this->Date = date('Y-m-d');
        parent::populateDefaults();
    }

    public function getCMSFields()
    {
        /** @var FieldList $fields */
        $fields = parent::getCMSFields();

//        $fields->insertBefore('Categories', CheckboxField::create('NoAutoImage', 'Do not auto-insert the page image into the content'));
        $fields->insertAfter('FeaturedImages', CheckboxField::create('NoAutoImage', 'Do NOT auto-insert the page image into the content'));

        // Reorder some fields
        if($schedulerField = $fields->fieldByName('Root.Main.SoftScheduler')) {
            $fields->removeByName('SoftScheduler'); // compositefield (includes subfields), we need to remove and re-insert to prevent duplicate fields warning
            $fields->insertAfter('Content', $schedulerField);
        }

//        if($catsField = $fields->dataFieldByName('Categories')) $fields->insertBefore('Date', $catsField);
//        if($tagsField = $fields->dataFieldByName('Tags')) $fields->insertBefore('Date', $tagsField);


        return $fields;
    }

}