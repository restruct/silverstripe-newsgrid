<?php

namespace Restruct\SilverStripe\NewsGrid;

use Override;
use Page;
use Restruct\SilverStripe\Fields\GridFieldSimpleSiteTreeState;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;
use SilverStripe\View\Requirements;

class NewsGridHolder extends Page
{
    private static $table_name = 'NewsGridHolder';

    private static $singular_name = 'News section';

    private static $plural_name = 'News sections';

    private static $class_description = 'Create a page to contain your news items/archive';

    # Silverstripe 5 names for $class_description (above) and $cms_icon (below): SS5 before 5.4 reads only
    # $description, and every SS5 release reads only $icon ($cms_icon is SS6). SS6 reads neither, so both
    # pairs are declared to keep the page type described and iconed on both majors. Drop these when SS5
    # leaves the range.
    private static $description = 'Create a page to contain your news items/archive';

    private static $icon = 'restruct/silverstripe-newsgrid:client/images/newsholder.png';

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

            # Workaround, Silverstripe 6 only: admintweaks' SelectiveLumberjack swaps in its own add-new
            # button, which calls SiteTree::page_type_classes() - removed in SS6 - so rendering this grid
            # fatals and the News section cannot be edited. Lumberjack's own button already offers
            # NewsGridPage (show_in_sitetree is false), the only child this holder allows, so use that.
            # Remove once admintweaks' button no longer calls page_type_classes().
            $addNewComp = $config->getComponentByType(GridFieldSiteTreeAddNewButton::class);
            if ($addNewComp && get_class($addNewComp) !== GridFieldSiteTreeAddNewButton::class
                && !method_exists(SiteTree::class, 'page_type_classes')
            ) {
                $config->addComponent(new GridFieldSiteTreeAddNewButton('buttons-before-left'), $addNewComp);
                $config->removeComponent($addNewComp);
            }
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

                # Restored (issue #1, "Lost bugfixes"): the news items grid sits on the Main tab directly
                # below Content, not on Lumberjack's separate 'ChildPages' tab. The SS6 WIP had commented
                # this out. The two lines above are the pre-2.0.10 form: they pass the arguments in the
                # SS4 order (field first), which from SS5 on is (name to insert after, field) - the fix
                # that 2.0.10 carried on the ss345 branch only. removeByName() drops Lumberjack's tab,
                # which is also named 'ChildPages', along with the grid inside it.
                $fields->removeByName('ChildPages');
                $fields->insertAfter('Content', $newsItemsGridField);
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
