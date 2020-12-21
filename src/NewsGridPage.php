<?php

namespace Restruct\NewsGrid {

    use Page;
    use SilverStripe\Forms\CheckboxField;
    use SilverStripe\Forms\DateField;

    class NewsGridPage extends Page
    {

        private static $table_name = 'NewsGridPage';


        private static $singular_name = 'NewsItem';
        private static $plural_name = 'NewsItems';
        private static $description = 'Create a news item';

        private static $can_be_root = false;
        private static $show_in_sitetree = false; //@TODO: fix this... (why is the config not being applied?)
        //private static $allowed_children = "none";

        private static $icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

        public  function singular_name()
        {
            parent::singular_name();
            return _t('MED.Newsitem', 'Nieuwsbericht');
        }

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
            //$this->Date = date('dd-MM-yyyy');
            $this->Date = date('Y-m-d');
            parent::populateDefaults();
        }

        public function getCMSFields()
        {
            $fields = parent::getCMSFields();

            $Datepckr = DateField::create('Date');
            //$Datepckr->setConfig('dateformat', 'dd-MM-yyyy'); // global setting
            //$Datepckr->setConfig('showcalendar', 1); // field-specific setting
            $fields->addFieldToTab("Root.Main", $Datepckr, 'Content');

            $fields->insertBefore('Categories', CheckboxField::create('NoAutoImage', 'Do not auto-insert the page image into the content'));

            return $fields;
        }

    }
}