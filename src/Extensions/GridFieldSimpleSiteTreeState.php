<?php

namespace Restruct\SilverStripe\Fields {

    use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;

    /**
     * Simplify the SiteTreeState from Lumberjack, to only display status (not date & time)
     **/
    class GridFieldSimpleSiteTreeState extends GridFieldSiteTreeState
    {

        public function getColumnContent($gridField, $record, $columnName)
        {
            if ( $columnName === "State" ) {
                if ( $record->hasMethod("isPublished") ) {
                    $modifiedLabel = "";
                    if ( $record->isModifiedOnStage ) {
                        $modifiedLabel = "<span class='modified'>" . _t("GridFieldSiteTreeState.Modified") . "</span>";
                    }

                    $published = $record->isPublished();
                    if ( !$published ) {
                        return _t(
                            "GridFieldSimpleSiteTreeState.Draft",
                            '<i class="btn-icon gridfield-icon btn-icon-pencil"></i> Draft',
                            "State for when a post is saved."
                        );
                    } else {
                        return _t(
                                "GridFieldSimpleSiteTreeState.Published",
                                '<i class="btn-icon gridfield-icon btn-icon-accept"></i> Published',
                                "State for when a post is published."
                            ) . $modifiedLabel;
                    }
                }
            }
        }

    }
}