<?php

namespace Restruct\SilverStripe\NewsGrid;

use Override;
use Page;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

class NewsGridPage extends Page
{
    private static $table_name = 'NewsGridPage';

    private static $singular_name = 'NewsItem';

    private static $plural_name = 'NewsItems';

    private static $class_description = 'Create a news item';

    # Silverstripe 5 names for $class_description and $cms_icon. SS 5.4 reads $class_description and falls
    # back to the deprecated $description only when that is empty, so on 5.4 $description is unused; it
    # is kept for older SS5 releases that ^5 still allows (not tested here). SS5 (5.4 checked) takes the
    # icon only from $icon; $cms_icon is the SS6 name. SS6 reads neither. Drop these when SS5 leaves the range.
    private static $description = 'Create a news item';

    private static $icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

    private static $can_be_root = false;

    private static $show_in_sitetree = false;

    //private static $allowed_children = "none";

    private static $cms_icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

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
        # 3.1.0: 'd M Y' rendered "2 1 2026". These are CLDR patterns, not PHP date(): M is the month
        # number and Y the week-year, which differs from the calendar year around 1 January.
        # 'd MMM y' is day, abbreviated month name and calendar year ("2 Jan 2026").
        # No locale is passed, so the month name follows i18n::get_locale() (nl_NL: "2 jan 2026").
        //return $this->obj('Date')->Format('d M Y');
        return $this->obj('Date')->Format('d MMM y');
    }

    // Filterable module: optionally add some extra info/remark to date, eg 'X minutes ago'
    public function DateFieldComment()
    {
        $date = $this->getDateField();
        // provided by FilterableArchive module
        if ($date->isToday() && (int) $date->TimeDiffIn('minutes') <= 60) {
            return sprintf('(%s)', $date->ago());
        }

        return null;
    }

    #[Override]
    public function populateDefaults()
    {
        $this->Date = date('Y-m-d');
        parent::populateDefaults();
    }

    #[Override]
    public function getCMSFields()
    {
        /** @var FieldList $fields */
        $fields = parent::getCMSFields();

//        $fields->insertBefore('Categories', CheckboxField::create('NoAutoImage', 'Do not auto-insert the page image into the content'));
        $fields->insertAfter('FeaturedImages',
            CheckboxField::create('NoAutoImage', _t('NewsGrid.NoAutoInsertImage', 'Do NOT auto-insert the page image into the content'))
        );

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
