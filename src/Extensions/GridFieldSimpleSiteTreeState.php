<?php

namespace Restruct\SilverStripe\Fields {

    use Override;
    use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;

    /**
     * Simplify the SiteTreeState from Lumberjack, to only display status (not date & time)
     **/
    class GridFieldSimpleSiteTreeState extends GridFieldSiteTreeState
    {

        #[Override]
        public function getColumnContent($gridField, $record, $columnName)
        {
            if ($columnName == "State" && $record->hasMethod("isPublished")) {
                $GFSiteTreeStateClass = parent::class;
                $modifiedLabel = '';
                if ( $record->isModifiedOnDraft() ) {
                    $modifiedLabel = '<span class="modified">' . _t($GFSiteTreeStateClass . '.Modified', 'Modified') . '</span>';
                }
                $published = $record->isPublished();
                if ( !$published ) {
                    return '<i class="font-icon-pencil btn--icon-md"></i>' . _t(
                        "SilverStripe\CMS\Controllers\ContentController.DRAFT",
                        'Saved as Draft'
                    );
                }
                return '<i class="font-icon-check-mark-circle btn--icon-md"></i>' . _t(
                        "\SilverStripe\Lumberjack\Forms\GridFieldSimpleSiteTreeState.Published",
                        'SilverStripe\CMS\Controllers\ContentController.PUBLISHED'
                    ) . $modifiedLabel;
            }
            return null;
        }

    }
}
