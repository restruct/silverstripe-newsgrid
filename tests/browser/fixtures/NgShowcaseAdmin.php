<?php

namespace Restruct\NgBrowser;

use Restruct\SilverStripe\NewsGrid\NewsGridHolder;
use Restruct\SilverStripe\NewsGrid\NewsGridPage;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

/**
 * BROWSER-TEST FIXTURE ONLY - the neutral demo content behind the README screenshots
 * (tests/browser/screenshots/readme.shot.ts):
 * GET /admin/ng-showcase/seed answers {"id": section ID, "link": its URL, "items": {title: ID}}.
 *
 * Seeds a published "News" section with seven published news items, each with a date, two
 * paragraphs and a featured image drawn with GD (abstract shapes, no photos), plus an "About us"
 * page so the site tree shows the section next to ordinary pages. Every call starts over: the
 * previous showcase pages are archived first. The browser specs never call it; their own content
 * comes from NgBResetAdmin.
 *
 * A LeftAndMain for the same reason as NgBResetAdmin (routed by url_segment, no YAML; only the
 * logged-in admin can call it), kept out of the CMS menu. Never loaded by a real install
 * (tests/browser/ carries a _manifest_exclude marker).
 */
class NgShowcaseAdmin extends LeftAndMain
{
    private static $url_segment = 'ng-showcase';

    private static $menu_title = 'Newsgrid showcase';

    # Not in the CMS menu: the screenshots show the CMS as a project would have it
    private static $ignore_menuitem = true;

    private static $allowed_actions = ['seed'];

    /** Folder in Files for the drawn images (featuredimages' own default is 'pageimages'). */
    private const FOLDER = 'news-showcase';

    /**
     * Title, date, two paragraphs, and the image's palette (background gradient top, bottom, shape
     * colour). Newest last here; the section lists them newest first (default_sort Date DESC).
     */
    private const ITEMS = [
        ['Road works on Station Street', '2026-03-02',
            'From Monday the north end of Station Street is closed to through traffic for six weeks while the water mains are replaced.',
            'Buses use the diversion via Mill Lane. Shops and homes stay reachable on foot throughout.',
            [0x33, 0x41, 0x55], [0x94, 0xa3, 0xb8], [0xf5, 0x9e, 0x0b]],
        ['Workshop: getting started with photography', '2026-03-16',
            'A free evening workshop for beginners: light, framing and getting more out of the camera you already own.',
            'Bring a phone or any camera. Places are limited to twelve, so sign up at the front desk.',
            [0x0f, 0x76, 0x6e], [0x99, 0xf6, 0xe4], [0xfb, 0xbf, 0x24]],
        ['New community garden opens', '2026-04-04',
            'The empty lot behind the sports hall is now a community garden with twenty raised beds and a shared tool shed.',
            'Beds are allotted for a season at a time. The first open planting day is Saturday morning.',
            [0x16, 0x65, 0x34], [0xbb, 0xf7, 0xd0], [0xdc, 0x26, 0x26]],
        ['Spring opening hours', '2026-04-20',
            'With the longer days the reading room stays open until eight in the evening on weekdays.',
            'Weekend hours are unchanged. The new hours apply until the end of September.',
            [0x1e, 0x40, 0xaf], [0xbf, 0xdb, 0xfe], [0xfd, 0xe0, 0x47]],
        ['Annual report 2025 published', '2026-05-11',
            'The annual report is out: visitor numbers, the year\'s projects and the accounts, in one short document.',
            'Printed copies are available at reception; questions about the figures are welcome by email.',
            [0x5b, 0x21, 0xb6], [0xdd, 0xd6, 0xfe], [0x22, 0xd3, 0xee]],
        ['Volunteers wanted for the summer festival', '2026-05-27',
            'The summer festival needs about forty volunteers for setting up, the info stand and clearing away afterwards.',
            'A shift is three hours and comes with a festival shirt and lunch. Sign up before the end of June.',
            [0xbe, 0x12, 0x3c], [0xfe, 0xcd, 0xd3], [0x1e, 0x29, 0x3b]],
        ['The members\' app gets a fresh look', '2026-06-08',
            'The members\' app has a new design: larger buttons, a clearer calendar and bookings in two taps.',
            'The update arrives automatically. Your account and bookings carry over as they are.',
            [0x0c, 0x4a, 0x6e], [0x7d, 0xd3, 0xfc], [0xf4, 0x72, 0xb6]],
    ];

    public function seed(HTTPRequest $request): HTTPResponse
    {
        foreach (SiteTree::get()->filter('URLSegment', ['news', 'about-us']) as $old) {
            foreach (NewsGridPage::get()->filter('ParentID', $old->ID) as $item) {
                $item->doArchive();
            }
            $old->doArchive();
        }

        # An ordinary page above the section, so the site tree is not just the one node
        $about = \Page::create(['Title' => 'About us', 'URLSegment' => 'about-us', 'Sort' => 1,
            'Content' => '<p>Who we are and what we do.</p>']);
        $about->write();
        $about->publishSingle();

        $holder = NewsGridHolder::create([
            'Title' => 'News',
            'URLSegment' => 'news',
            'Sort' => 2,
            'Content' => '<p>Announcements, events and updates from around the neighbourhood.</p>',
        ]);
        $holder->write();
        $holder->publishSingle();

        Folder::find_or_make(self::FOLDER);
        $ids = [];
        foreach (self::ITEMS as $i => [$title, $date, $p1, $p2, $top, $bottom, $shape]) {
            $item = NewsGridPage::create([
                'Title' => $title,
                'ParentID' => $holder->ID,
                'Date' => $date,
                'Content' => '<p>' . htmlspecialchars($p1) . '</p><p>' . htmlspecialchars($p2) . '</p>',
            ]);
            $item->write();
            $image = $this->drawImage(self::FOLDER . '/news-' . ($i + 1) . '.jpg', $title, $top, $bottom, $shape, $i);
            if ($image) {
                $item->FeaturedImages()->add($image, ['SortOrder' => 1]);
            }
            # publishRecursive: the item owns its featured image (featuredimages' $owns)
            $item->publishRecursive();
            $ids[$title] = $item->ID;
        }

        return HTTPResponse::create(json_encode(['id' => $holder->ID, 'link' => $holder->Link(), 'items' => $ids]))
            ->addHeader('Content-Type', 'application/json');
    }

    /**
     * An abstract 960 x 600 JPG: a vertical gradient with a sun-like disc and two hills, the disc
     * moved per item so the images do not look identical. Null without GD.
     */
    private function drawImage(string $filename, string $title, array $top, array $bottom, array $shape, int $n): ?Image
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }
        $existing = Image::get()->filter('FileFilename', $filename)->first();
        if ($existing) {
            $existing->deleteFile();
            $existing->doArchive();
        }

        $w = 960;
        $h = 600;
        $gd = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $f = $y / ($h - 1);
            $rgb = array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $f), $top, $bottom);
            imageline($gd, 0, $y, $w - 1, $y, imagecolorallocate($gd, ...$rgb));
        }
        $cx = 180 + ($n * 113) % 600;
        imagefilledellipse($gd, $cx, 220, 190, 190, imagecolorallocate($gd, ...$shape));
        # Hills: darker shades of the top colour
        $dark = array_map(fn ($c) => (int) ($c * 0.75), $top);
        $darker = array_map(fn ($c) => (int) ($c * 0.5), $top);
        imagefilledpolygon($gd, [0, 430, 220, 330, 480, 420, 720, 310, 960, 400, 960, 600, 0, 600], imagecolorallocate($gd, ...$dark));
        imagefilledpolygon($gd, [0, 500, 300, 420, 600, 490, 960, 440, 960, 600, 0, 600], imagecolorallocate($gd, ...$darker));

        ob_start();
        imagejpeg($gd, null, 82);
        $bytes = (string) ob_get_clean();

        $image = Image::create();
        $image->setFromString($bytes, $filename);
        # Named after its news item, as an editor would; the default title is the file name
        $image->Title = $title;
        $image->write();
        # The CMS never generates thumbnails on display (asset-admin "Generates: false"), so an image
        # written from code would show an empty tile in the item's upload field without this.
        AssetAdmin::singleton()->generateThumbnails($image);

        return $image;
    }
}
