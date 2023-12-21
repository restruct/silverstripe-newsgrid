<?php

namespace Restruct\SilverStripe\NewsGrid;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\View\Requirements;

class NewsGridHolder
    extends \Page
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
        return _t('NEWSGRID.NewsItems', 'Nieuwsberichten');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // LumberJack
        /** @var GridField $newsItemsGridField */
        if ($newsItemsGridField = $fields->dataFieldByName('ChildPages')) {
            /** @var GridFieldConfig $config */
            $config = $newsItemsGridField->getConfig();
            /** @var GridFieldDataColumns $dataColumns */
            $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
            $displayfields = $dataColumns->getDisplayFields($newsItemsGridField);
            $displayfields[ 'Date' ] = 'Date';
            $displayfields[ 'ScheduledStatusDataColumn' ] = 'Scheduling';
            $dataColumns->setDisplayFields($displayfields);

            Requirements::customCSS('.table td.col-ScheduledStatusDataColumn {
                padding-top: .1rem;
                padding-bottom: .1rem;
                vertical-align: middle;
                }', 'ScheduledStatusDataColumnTweaks');

            // Make Content field slightly smaller and move newsitems below it
            if($ContentField = $fields->dataFieldByName('Content')) {
                $ContentField->setRows(10)->removeExtraClass('stacked');

                $fields->removeByName('ChildPages');
                $fields->insertAfter($newsItemsGridField, 'Content');
            }
        }

        return $fields;
    }

    // Custom getter to return NewsGridPage directly instead of SiteTree (allows sorting on Date which SiteTree doesn't)
    public function getLumberjackPagesForGridfield($excluded = [])
    {
        return NewsGridPage::get()->filter('ParentID', $this->ID);
    }
}
