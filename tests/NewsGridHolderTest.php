<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\Silverstripe\AdminTweaks\Extensions\SelectiveLumberjack;
use Restruct\SilverStripe\Fields\GridFieldSimpleSiteTreeState;
use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeAddNewButton;
use SilverStripe\Lumberjack\Forms\GridFieldSiteTreeState;
use SilverStripe\i18n\i18n;

/**
 * Behavioural tests for the news section page type (NewsGridHolder).
 *
 * The module's page types extend the host project's Page, so the host must provide Page and
 * PageController (every real Silverstripe project does; the CI host writes minimal ones).
 *
 * Compatibility note: this suite runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11
 * (Silverstripe 6). Keep it free of doc-comment metadata (@test, @dataProvider) and of
 * assertions removed after PHPUnit 9.
 */
class NewsGridHolderTest extends SapphireTest
{
    protected $usesDatabase = true;

    private function makeHolder(string $title = 'News'): NewsGridHolder
    {
        $holder = NewsGridHolder::create(['Title' => $title]);
        $holder->write();

        return $holder;
    }

    private function makeItem(NewsGridHolder $holder, string $title, string $date = '2026-01-02'): NewsGridPage
    {
        $item = NewsGridPage::create(['Title' => $title, 'ParentID' => $holder->ID, 'Date' => $date]);
        $item->write();

        return $item;
    }

    /**
     * Flat list of the field names in a tab, in order.
     */
    private function tabFieldNames(FieldList $fields, string $tabPath): array
    {
        $tab = $fields->fieldByName($tabPath);
        $this->assertNotNull($tab, "Tab $tabPath should exist");

        return array_map(fn ($field) => $field->getName(), $tab->Fields()->toArray());
    }

    // ---------------------------------------------------------------- wiring and config

    public function testSelectiveLumberjackIsApplied()
    {
        $this->assertTrue(NewsGridHolder::singleton()->hasExtension(SelectiveLumberjack::class));
    }

    public function testOnlyNewsItemsAreAllowedAsChildren()
    {
        // Declared both as a static and in YAML, so the merged config lists it twice; what matters is
        // that nothing else is allowed
        $this->assertSame(
            [NewsGridPage::class],
            array_values(array_unique(NewsGridHolder::singleton()->allowedChildren()))
        );
    }

    public function testClassDescriptionIsSetOnBothMajors()
    {
        // SS6 and SS5.4+ read class_description, older SS5 only description; the module declares both
        $this->assertSame(
            'Create a page to contain your news items/archive',
            NewsGridHolder::singleton()->classDescription()
        );
    }

    public function testDutchClassDescriptionIsTranslatedOnBothMajors()
    {
        // SS6 only looks up the CLASS_DESCRIPTION key; SS5 also tries the legacy DESCRIPTION key
        i18n::with_locale('nl_NL', function () {
            $this->assertSame(
                'Maak een pagina aan voor uw nieuwsberichten/archief',
                NewsGridHolder::singleton()->i18n_classDescription()
            );
        });
    }

    public function testEnglishStringsAreRegisteredForTheEnglishLocale()
    {
        // Regression: lang/en.yml was keyed 'nl:', so the English strings were never loaded
        // for an English CMS and the Dutch _t() default leaked through.
        i18n::with_locale('en_US', function () {
            $this->assertSame('Newsitems', _t('NEWSGRID.NewsItems', 'Nieuwsberichten'));
        });
    }

    public function testCmsIconResolvesToTheModuleImage()
    {
        // SS6 reads cms_icon through CMSMain, SS5 reads icon through the page itself
        if (method_exists(CMSMain::class, 'getRecordIconUrl')) {
            $url = CMSMain::singleton()->getRecordIconUrl(NewsGridHolder::class);
        } else {
            $url = NewsGridHolder::singleton()->getPageIconURL();
        }
        $this->assertNotNull($url);
        $this->assertStringContainsString('newsholder.png', $url);
    }

    /**
     * A CMS pages controller editing $holder, pushed as the current controller, as in the real CMS.
     */
    private function pushCmsController(NewsGridHolder $holder, string $action = 'show'): CMSMain
    {
        $request = new HTTPRequest('GET', 'admin/pages/edit/show/' . $holder->ID);
        $request->setSession(new Session([]));
        $request->setRouteParams(['ID' => $holder->ID]);

        $controller = CMSMain::create();
        $controller->setRequest($request);
        // Controller::$action is protected and only set while handling a request; set it directly
        $property = new \ReflectionProperty(Controller::class, 'action');
        $property->setAccessible(true);
        $property->setValue($controller, $action);
        $controller->pushCurrent();

        return $controller;
    }

    // ---------------------------------------------------------------- CMS fields

    public function testNewsItemsGridSitsOnTheMainTabDirectlyBelowContent()
    {
        // Regression for issue #1 ("Lost bugfixes"): the grid belongs on the Main tab under Content,
        // not on Lumberjack's separate 'ChildPages' tab.
        $fields = $this->makeHolder()->getCMSFields();

        $mainNames = $this->tabFieldNames($fields, 'Root.Main');
        $contentPos = array_search('Content', $mainNames, true);
        $this->assertNotFalse($contentPos, 'Content should be on the Main tab');
        $this->assertSame('ChildPages', $mainNames[$contentPos + 1] ?? null);

        $this->assertInstanceOf(GridField::class, $fields->dataFieldByName('ChildPages'));
        $this->assertNull($fields->fieldByName('Root.ChildPages'), "Lumberjack's own tab should be gone");
    }

    public function testContentFieldIsShortened()
    {
        $content = $this->makeHolder()->getCMSFields()->dataFieldByName('Content');
        $this->assertEquals(10, $content->getRows());
        $this->assertFalse($content->hasExtraClass('stacked'));
    }

    public function testStateColumnUsesTheSimplifiedComponent()
    {
        $config = $this->makeHolder()->getCMSFields()->dataFieldByName('ChildPages')->getConfig();
        $state = $config->getComponentByType(GridFieldSiteTreeState::class);

        $this->assertInstanceOf(GridFieldSimpleSiteTreeState::class, $state);
        $this->assertCount(1, $config->getComponentsByType(GridFieldSiteTreeState::class));
    }

    public function testDisplayColumnsWithoutDateConfigShowTitleOnly()
    {
        Config::modify()->remove(NewsGridHolder::class, 'managed_object_date_field');
        $columns = $this->makeHolder()->getCMSFields()->dataFieldByName('ChildPages')
            ->getConfig()->getComponentByType(GridFieldDataColumns::class);

        // Soft scheduler is not installed here, so its column must not be added
        $this->assertSame(['Title' => 'Title'], $columns->getDisplayFields(null));
    }

    public function testConfiguredDateFieldAddsADateColumn()
    {
        Config::modify()->set(NewsGridHolder::class, 'managed_object_date_field', 'Date');
        $columns = $this->makeHolder()->getCMSFields()->dataFieldByName('ChildPages')
            ->getConfig()->getComponentByType(GridFieldDataColumns::class);

        $this->assertSame(['Title' => 'Title', 'Date' => 'Date'], $columns->getDisplayFields(null));
    }

    public function testGridListsOnlyThisHoldersNewsItems()
    {
        $holder = $this->makeHolder('News A');
        $other = $this->makeHolder('News B');
        $mine = $this->makeItem($holder, 'Mine');
        $this->makeItem($other, 'Not mine');

        $list = $holder->getLumberjackPagesForGridfield();

        $this->assertSame(NewsGridPage::class, $list->dataClass());
        $this->assertSame([$mine->ID], $list->column('ID'));
    }

    public function testGridRendersWithTheItemsAndTheirState()
    {
        $holder = $this->makeHolder();
        $item = $this->makeItem($holder, 'Rendered item');
        $item->publishRecursive();
        $this->makeItem($holder, 'Draft item');

        // The grid's add-new button asks the current CMS controller for the page being edited
        $controller = $this->pushCmsController($holder);
        try {
            $fields = $holder->getCMSFields();
            // a FormField needs to belong to a Form before it can render: it calls Link()
            \SilverStripe\Forms\Form::create($controller, 'EditForm', $fields, FieldList::create());
            $html = (string) $fields->dataFieldByName('ChildPages')->FieldHolder();
        } finally {
            $controller->popCurrent();
        }

        $this->assertStringContainsString('Rendered item', $html);
        $this->assertStringContainsString('Draft item', $html);
        $this->assertStringContainsString('font-icon-check-mark-circle', $html);
        $this->assertStringContainsString('font-icon-pencil', $html);
    }

    public function testAddNewButtonOffersANewsItemToALoggedInEditor()
    {
        // The button only renders for someone who may create a child page; testGridRendersWith...
        // above runs anonymously and so never reaches it. On Silverstripe 6 this is also the check on
        // the add-new workaround in getCMSFields(): admintweaks' own button fatals there.
        $this->logInWithPermission('ADMIN');
        $holder = $this->makeHolder();

        $controller = $this->pushCmsController($holder);
        try {
            $fields = $holder->getCMSFields();
            \SilverStripe\Forms\Form::create($controller, 'EditForm', $fields, FieldList::create());
            $gridField = $fields->dataFieldByName('ChildPages');
            $button = $gridField->getConfig()->getComponentByType(GridFieldSiteTreeAddNewButton::class);
            $this->assertNotNull($button, 'The grid should have an add-new button');
            $allowed = $button->getAllowedChildren($holder);
            $html = (string) $gridField->FieldHolder();
        } finally {
            $controller->popCurrent();
        }

        $this->assertSame([NewsGridPage::class], array_keys($allowed));
        $this->assertStringContainsString('Add new', $html);
    }

    // ---------------------------------------------------------------- CMS site tree

    /**
     * Children the CMS site tree would show for $holder, with a CMS controller on the given action.
     */
    private function cmsTreeChildTitles(NewsGridHolder $holder, string $action): array
    {
        $controller = $this->pushCmsController($holder, $action);
        try {
            // SS6 builds the tree from getChildrenForTree(), SS5 from AllChildrenIncludingDeleted()
            $children = $holder->hasMethod('getChildrenForTree')
                ? $holder->getChildrenForTree()
                : $holder->AllChildrenIncludingDeleted();

            return $children->column('Title');
        } finally {
            $controller->popCurrent();
        }
    }

    public function testNewsItemsAreHiddenFromTheCmsSiteTree()
    {
        $holder = $this->makeHolder();
        $this->makeItem($holder, 'Tree item');

        $this->assertSame([], $this->cmsTreeChildTitles($holder, 'treeview'));
        $this->assertSame([], $this->cmsTreeChildTitles($holder, 'getsubtree'));
    }

    public function testNewsItemsAreStillChildrenOutsideTheCmsTree()
    {
        // Control for the test above: the item exists and is a child; only the CMS tree hides it
        $holder = $this->makeHolder();
        $this->makeItem($holder, 'Tree item');

        $this->assertSame(['Tree item'], $holder->stageChildren(true)->column('Title'));
    }
}
