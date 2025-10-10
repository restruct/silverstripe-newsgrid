<?php

namespace Restruct\SilverStripe\NewsGrid;

use Override;
use Page;
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

    private static $class_description = 'Create a page to contain your news items/archive';

    private static $allowed_children = [ NewsGridPage::class ];

    private static $apply_sortable = false;

    private static $cms_icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

    private static $has_one = [];

    public function getLumberjackTitle()
    {
        return _t('NEWSGRID.NewsItems', 'Nieuwsberichten');
    }

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $configuredDatefield = $this->config()->get('managed_object_date_field');

        // LumberJack
        /** @var GridField $newsItemsGridField */
        if ($newsItemsGridField = $fields->dataFieldByName('ChildPages')) {
            /** @var GridFieldConfig $config */
            $config = $newsItemsGridField->getConfig();
            // Replace GridFieldSiteTreeState with simplified version
            $SiteTreeStateComp = $config->getComponentByType(GridFieldSiteTreeState::class);
            $config->addComponent(new GridFieldSimpleSiteTreeState(), $SiteTreeStateComp);
            $config->removeComponent($SiteTreeStateComp);
            /** @var GridFieldDataColumns $dataColumns */
            $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);

            // Set explicit display fields to avoid DataList rendering issues
            $displayfields = [
                'Title' => 'Title',
            ];

            // Only add date field if configured
            if ($configuredDatefield) {
                $displayfields[ $configuredDatefield ] = 'Date';
            }

            // Only add ScheduledStatusDataColumn if SoftScheduler is installed
            if (class_exists('Restruct\SilverStripe\SoftScheduler\EmbargoExpiryExtension')) {
                $displayfields[ 'ScheduledStatusDataColumn' ] = 'Scheduling';
                Requirements::customCSS('.table td.col-ScheduledStatusDataColumn {
                        padding-top: .1rem;
                        padding-bottom: .1rem;
                        vertical-align: middle;
                    }', 'ScheduledStatusDataColumnTweaks');
            }

            $dataColumns->setDisplayFields($displayfields);

            // Make Content field slightly smaller and move newsitems below it
            if($ContentField = $fields->dataFieldByName('Content')) {
                $ContentField->setRows(10)->removeExtraClass('stacked');

                //$fields->removeByName('ChildPages');
                //$fields->insertAfter($newsItemsGridField, $ContentField);
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
