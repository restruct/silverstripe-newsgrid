<?php

namespace Restruct\SilverStripe\NewsGrid\Tests;

use Restruct\SilverStripe\FeaturedImages\FeaturedImageExtension;
use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;

/**
 * Behavioural tests for the news item page type (NewsGridPage).
 *
 * Compatibility note: runs under PHPUnit 9 (Silverstripe 5) and PHPUnit 11 (Silverstripe 6).
 */
class NewsGridPageTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * News items cannot live at the root (can_be_root is false), so each gets a holder.
     */
    private function makeItem(array $data): NewsGridPage
    {
        $holder = NewsGridHolder::get()->first();
        if (!$holder) {
            $holder = NewsGridHolder::create(['Title' => 'News']);
            $holder->write();
        }
        $item = NewsGridPage::create(array_merge(['ParentID' => $holder->ID], $data));
        $item->write();

        return $item;
    }

    // ---------------------------------------------------------------- schema and config

    public function testSchemaHasTheNewsItemFields()
    {
        $schema = DataObject::getSchema();
        $this->assertSame('NewsGridPage', $schema->tableName(NewsGridPage::class));
        $this->assertSame('Date', $schema->fieldSpec(NewsGridPage::class, 'Date', DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED));
        $this->assertSame('Boolean', $schema->fieldSpec(NewsGridPage::class, 'NoAutoImage', DataObjectSchema::DB_ONLY | DataObjectSchema::UNINHERITED));

        // and the temp database actually has the columns, not just the config
        $columns = array_keys(DB::field_list('NewsGridPage'));
        $this->assertContains('Date', $columns);
        $this->assertContains('NoAutoImage', $columns);
    }

    public function testFeaturedImagesAreAppliedAndWritable()
    {
        $this->assertTrue(NewsGridPage::singleton()->hasExtension(FeaturedImageExtension::class));

        $item = $this->makeItem(['Title' => 'With image']);
        $image = Image::create(['Title' => 'img']);
        $image->write();
        $item->FeaturedImages()->add($image);

        $this->assertSame([$image->ID], $item->FeaturedImages()->column('ID'));
    }

    public function testItemsCannotBeRootAndStayOutOfTheSiteTreeConfig()
    {
        $config = NewsGridPage::config();
        $this->assertFalse($config->get('can_be_root'));
        $this->assertFalse($config->get('show_in_sitetree'));
        $this->assertSame(
            [NewsGridPage::class],
            NewsGridHolder::config()->get('hide_from_cms_tree')
        );
    }

    public function testItemsSortNewestFirst()
    {
        $this->makeItem(['Title' => 'Older', 'Date' => '2025-01-01']);
        $this->makeItem(['Title' => 'Newer', 'Date' => '2026-01-01']);

        $this->assertSame(['Newer', 'Older'], NewsGridPage::get()->column('Title'));
    }

    public function testClassDescriptionIsSetOnBothMajors()
    {
        $this->assertSame('Create a news item', NewsGridPage::singleton()->classDescription());
    }

    public function testCmsIconResolvesToTheModuleImage()
    {
        if (method_exists(CMSMain::class, 'getRecordIconUrl')) {
            $url = CMSMain::singleton()->getRecordIconUrl(NewsGridPage::class);
        } else {
            $url = NewsGridPage::singleton()->getPageIconURL();
        }
        $this->assertNotNull($url);
        $this->assertStringContainsString('newsholder.png', $url);
    }

    // ---------------------------------------------------------------- defaults and fields

    public function testNewItemDefaultsToToday()
    {
        $this->assertSame(date('Y-m-d'), NewsGridPage::create()->Date);
    }

    public function testNoAutoImageCheckboxFollowsTheFeaturedImagesField()
    {
        $item = $this->makeItem(['Title' => 'Fields']);
        $fields = $item->getCMSFields();

        $checkbox = $fields->dataFieldByName('NoAutoImage');
        $this->assertInstanceOf(CheckboxField::class, $checkbox);

        $names = array_map(fn ($field) => $field->getName(), $fields->fieldByName('Root.Main')->Fields()->toArray());
        $imagesPos = array_search('FeaturedImages', $names, true);
        $this->assertNotFalse($imagesPos, 'FeaturedImages field should be on the Main tab');
        $this->assertSame('NoAutoImage', $names[$imagesPos + 1] ?? null);
    }
}
