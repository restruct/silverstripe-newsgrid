<?php

namespace Restruct\NewsGrid {

    use Page;
    use Restruct\Essentials\Fields\GridFieldSimpleSiteTreeState;
    use SilverStripe\Forms\GridField\GridFieldDataColumns;
    use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;

    class NewsGridHolder extends Page
    {

        private static $table_name = 'NewsGridHolder';

        private static $singular_name = 'News section';
        private static $plural_name = 'News sections';
        private static $description = 'Create a page to contain your news items/archive';

        private static $allowed_children = [ NewsGridPage::class ];
        private static $apply_sortable = false;

        private static $icon = 'restruct-apps/silverstripe-newsgrid:client/images/newsholder.png';

        private static $has_one = [];

        function getLumberjackTitle()
        {
            return _t('NewsGrid.NewsItems', 'Nieuwsberichten');
        }

        public function getCMSFields()
        {

            // update relationlist to specific/correct class (instead of general SiteTree) to be able to sort on Date in gridfield
            $this->afterExtending('updateCMSFields', function ($fields) {
                $excluded = $this->getExcludedSiteTreeClassNames();
                if ( count($excluded) == 1 ) {
                    $excludedClass = array_pop($excluded);
                    $pages = $excludedClass::get()->filter([
                        'ParentID' => $this->owner->ID,
                    ]);
                    $fields->dataFieldByName('ChildPages')->setList($pages);
                }
            });

            $fields = parent::getCMSFields();

            $gfconf = $fields->dataFieldByName('ChildPages')->getConfig();

            // simplify status column
            $gfconf->removeComponentsByType(GridFieldSiteTreeState::class);
            $gfconf->addComponent(new GridFieldSimpleSiteTreeState());

            // add scheduled status column
            $dataColumns = $gfconf->getComponentByType(new GridFieldDataColumns());
            $dataColumns->setDisplayFields([
                'Title'                     => 'Title',
                'Date'                      => 'Date',
                'ScheduledStatusDataColumn' => 'Scheduling',
            ]);

            return $fields;
        }


    }

}