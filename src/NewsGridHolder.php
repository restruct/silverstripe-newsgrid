<?php

namespace Restruct\SilverStripe\NewsGrid {

    use HLCL\Models\SpecialistRole;
    use Page;
    use Restruct\Essentials\Fields\GridFieldConfig_EditableOrderable;
    use Restruct\SilverStripe\Fields\GridFieldSimpleSiteTreeState;
    use SilverStripe\Forms\GridField\GridField;
    use SilverStripe\Forms\GridField\GridFieldConfig;
    use SilverStripe\Forms\GridField\GridFieldDataColumns;
    use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;
    use SilverStripe\View\Requirements;

    class NewsGridHolder extends Page
    {

        private static $table_name = 'NewsGridHolder';

        private static $singular_name = 'News section';
        private static $plural_name = 'News sections';
        private static $description = 'Create a page to contain your news items/archive';

        private static $allowed_children = [ NewsGridPage::class ];
        private static $apply_sortable = false;

        private static $icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

        private static $has_one = [];

        function getLumberjackTitle()
        {
            return _t('NewsGrid.NewsItems', 'Nieuwsberichten');
        }

        public function getCMSFields()
        {
            // update relationlist to specific/correct class (instead of general SiteTree) to be able to sort on Date in gridfield
//            $this->afterExtending('updateCMSFields', function ($fields) {
//                $excluded = $this->getExcludedSiteTreeClassNames();
//                if ( count($excluded) == 1 ) {
//                    $excludedClass = array_pop($excluded);
//                    $pages = $excludedClass::get()->filter([
//                        'ParentID' => $this->owner->ID,
//                    ]);
//                    $fields->dataFieldByName('ChildPages')->setList($pages);
//                }
//            });

            $fields = parent::getCMSFields();

//            // GridFieldPages
//            $gfconf = $fields->dataFieldByName('Subpages')->getConfig();
//            // simplify status column
//            $gfconf->removeComponentsByType(GridFieldSiteTreeState::class);
//            $gfconf->addComponent(new GridFieldSimpleSiteTreeState());
//            // add scheduled status column
//            $dataColumns = $gfconf->getComponentByType(new GridFieldDataColumns());
//            $dataColumns->setDisplayFields([
//                'Title'                     => 'Title',
//                'Date'                      => 'Date',
//                'ScheduledStatusDataColumn' => 'Scheduling',
//            ]);

            // LumberJack
            /** @var GridField $gf */
            $gf = $fields->dataFieldByName('ChildPages');
            if ( null !== $gf ) {
                /** @var GridFieldConfig $config */
                $config = $gf->getConfig();
                /** @var GridFieldDataColumns $dataColumns */
                $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
                $displayfields = $dataColumns->getDisplayFields($gf);
//                $displayfields = array_reverse($displayfields, true);
                $displayfields[ 'ScheduledStatusDataColumn' ] = 'Scheduling';
//                $dataColumns->setDisplayFields(array_reverse($displayfields, true));
                $dataColumns->setDisplayFields($displayfields);

                Requirements::customCSS('.table td.col-ScheduledStatusDataColumn {
                    padding-top: .1rem;
                    padding-bottom: .1rem;
                    vertical-align: middle;
                    }', 'ScheduledStatusDataColumnTweaks');
            }

            return $fields;
        }


    }

}