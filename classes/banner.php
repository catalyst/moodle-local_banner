<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A Banner record.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_banner;

use context_system;

require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die;

class banner {
    const BANNER_DEFAULT = 0;

    const BANNER_PLACEHOLDER = 1;

    public $id = null;

    public $course = null;

    public $context = null;

    public $file = null;

    public $cropx = null;

    public $cropy = null;

    public $scalex = null;

    public $scaley = null;

    public $height = null;

    public $width = null;

    public $rotate = null;

    public $filename = null;

    private $fs = null;

    private $cropw = null;

    private $croph = null;

    private $itemid = null;

    public function __construct($data = null) {
        // We still want to be able to call the banner filestorage if there was no $data.
        $this->fs = get_file_storage();

        if (is_null($data)) {
            return;
        }

        $this->set_data($data);
        $this->adjust_field_types();
    }

    public function save() {
        global $DB;

        // Fix the types before updating the database.
        $this->adjust_field_types();

        if (empty($this->id)) {
            return $DB->insert_record('local_banner', $this);
        } else {
            return $DB->update_record('local_banner', $this);
        }
    }

    public function delete() {
        global $DB;

        $purge = true;
        $this->invalidate_banner($purge);

        if (!empty($this->id)) {
            return $DB->delete_records('local_banner', array('id' => $this->id));
        }
    }

    public static function load_from_id($bannerid) {
        global $DB;

        $data = $DB->get_record('local_banner', array('id' => $bannerid));

        if ($data !== false) {
            return new banner($data);
        }

        // No record exists for $bannerid.
        return null;
    }

    public static function load_from_courseid($courseid) {
        global $DB;

        $data = $DB->get_record('local_banner', array('course' => $courseid));

        if ($data !== false) {
            return new banner($data);
        }

        // No record exists for $courseid.
        return null;
    }

    public function set_data($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Converts the type of the fields as needed.
     */
    private function adjust_field_types() {
        // Adjust int fields.
        $ft = array('id', 'course', 'context', 'file', 'cropx', 'cropy', 'scalex', 'scaley', 'height', 'width', 'rotate');
        foreach ($ft as $f) {
            $this->$f = ($this->$f === null) ? null : (int)$this->$f;
        }
    }

    private static function load_placeholder() {
        $fs = get_file_storage();
        $context = context_system::instance();
        $defaultfilename = get_config('local_banner', 'defaultbanner');

        // No placeholder has been found.
        if (empty($defaultfilename)) {
            return null;
        }

        $pathinfo = pathinfo($defaultfilename);
        $file = $fs->get_file($context->id, 'local_banner', 'placeholder', 0, $pathinfo['dirname'], $pathinfo['basename']);

        // Construct basic $data for a simple banner.
        $data = array(
            'context' => self::BANNER_PLACEHOLDER,
            'course'  => self::BANNER_PLACEHOLDER,
        );

        return new banner($data);
    }

    private function parse_ratio() {
        $config = get_config('local_banner', 'aspectratio');

        preg_match('/[:]/', $config, $matches);

        $ratiow = $config; // This could be a single integer in the config.
        $ratioh = 1;

        if ($matches) {
            $token = $matches[0];
            $exploded = explode($token, $config);

            if (count($exploded) == 2) {
                $ratiow = $exploded[0];
                $ratioh = $exploded[1];
            }
        }

        return array($ratiow, $ratioh);
    }

    public function get_ratio() {
        list($ratiow, $ratioh) = $this->parse_ratio();
        return $ratiow / $ratioh;
    }

    public static function generate($courseid, $width, $height, $original) {
        // Check to see if a banner exists.
        $banner = self::load_from_courseid($courseid);

        if (empty($banner)) {
            $banner = self::load_placeholder();
        }

        // No placeholder has been found.
        if (empty($banner)) {
            return null;
        }

        // When not viewing a course, provide the default banner.
        if ($courseid == 1) {
            $path = get_config('local_banner', 'defaultbanner');

            // This is the file that has been saved in the admin config settings.
            $context = 1;
            $itemid = 0;
            $file = $banner->fs->get_file($context, 'local_banner', 'placeholder', $itemid, '/', $path);
            return $file;
        }

        // Do not generate a file that is w/h 0.
        if (!$original && (empty($width) || empty($height))) {
            return null;
        }

        $banner->cropw = $width;
        $banner->croph = $height;
        $banner->itemid = $width . $height;

        if ($original) {
            $banner->itemid = self::BANNER_DEFAULT;
        }

        /*
         * Try to obtain the file with the width $itemid.
         * The false argument prevents directory lookup.
         * When specifying $itemid this _should_ return an array of one element.
         */
        $filearray = $banner->fs->get_area_files($banner->context, 'local_banner', 'banners', $banner->itemid, 'itemid', false);
        $file = array_shift($filearray);

        // File does not exist. Create it.
        if (empty($file)) {

            // No file has been saved for this course, return the default banner.
            if (empty($banner->file)) {
                $path = get_config('local_banner', 'defaultbanner');
                $contextid = 1;
                $itemid = 0;
                $file = $banner->fs->get_file($contextid, 'local_banner', 'placeholder', $itemid, '/', $path);

            } else {
                // This course has a banner fileid assigned to it. Lets create the banner / crop it again.
                $file = $banner->create_file();
            }
        }

        return $file;
    }

    public function invalidate_banner($purge = false) {
        $files = $this->fs->get_area_files($this->context, 'local_banner', 'banners', false, 'itemid', false);
        foreach ($files as $file) {
            if ($purge) {
                $file->delete();
            } else if ($file->get_itemid() != self::BANNER_DEFAULT) {
                $file->delete();
            }
        }
    }

    private function create_file() {
        $dir = make_request_directory();
        $tmpfile = tempnam($dir, 'banner_');
        $this->image_crop($tmpfile);

        $pathinfo = pathinfo($this->filename);
        $newfilename = $pathinfo['filename'] . '-' . $this->cropw . 'x' . $this->croph . '.' . $pathinfo['extension'];

        $record = array(
            'contextid' => $this->context,
            'component' => 'local_banner',
            'filearea' => 'banners',
            'itemid' => $this->itemid,
            'filepath' => '/',
            'filename' => $newfilename,
            'mimetype' => get_mimetype_for_sending($pathinfo['basename']),
            'source' => $pathinfo['basename'],
        );

        return $this->fs->create_file_from_pathname($record, $tmpfile);
    }

    private function image_crop($tmpfile) {
        $source = $this->fs->get_file_by_id($this->file);

        if (empty($source)) {
            return null;
        }

        // Create a new image from the file that we can process.
        $original = @imagecreatefromstring($source->get_content());
        if (empty($original)) {
            return null;
        }

        $imageinfo = @getimagesizefromstring($source->get_content());
        if (empty($imageinfo)) {
            return null;
        }

        // Set defaults.
        $dstx = 0; // x-coordinate of destination point.
        $dsty = 0; // y-coordinate of destination point.
        $srcx = $this->cropx; // x-coordinate of source point.
        $srcy = $this->cropy; // y-coordinate of source point.

        // URL parameters passed to the generation.
        $dstw = $this->cropw; // Destination width.
        $dsth = $this->croph; // Destination height.

        // Input image size.
        $srcw = $imageinfo[0]; // Source width.
        $srch = $imageinfo[1]; // Source height.

        // 1600 / 500 = 3.2
        $fileinputratio = $srcw / $srch;

        // 1000 / 120 = 8.3333
        $cropoutputratio = $dstw / $dsth;

        // 1600 / 1000 = 1.6
        $scalew = $srcw / $dstw;

        // 500 / 120 = 4.1667
        $scaleh = $srch / $dsth;

        // Scale multiplications.
        // Resulting width  1000 * 1.6 = 1600
        // Resulting height 120  * 1.6 = 192

        if ($fileinputratio < $cropoutputratio) {
            // Taking a horizontal slice.

            // Take the maximum width of the image.
            $srcw = $imageinfo[0];

            // The maximum height multiplied by the scale;
            $srch = $dsth * $scalew;

            // Starting at the left side of the image.
            $srcx = 0;

            // Find the center of the focus box.
            $focusy = $srcy + ($this->height / 2);

            // Subtract half the height of the result.
            $srcy = $focusy - ($srch / 2);

            // Fix upper out of bounds.
            if ($srcy < 0) {
                $srcy = 0;
            }

            // Fix lower out of bounds.
            if ($srcy + $srch > $imageinfo[1]) {
                $srcy = $imageinfo[1] - $srch;
            }

        } else {
            // Taking a vertical slice.

            // Take the maximum height of the image;
            $srch = $imageinfo[1];

            // The maximum width multiplied by the scale;
            $srcw = $dstw * $scaleh;

            // Starting at the top of the image.
            $srcy = 0;

            // Find the center of the focus box.
            $focusx = $srcx + ($this->width / 2);

            // Subtract half the width of the result.
            $srcx = $focusx - ($srcw / 2);

            // Fix left out of bounds.
            if ($srcx < 0) {
                $srcx = 0;
            }

            // Fix right out of bounds.
            if ($srcx + $srcw > $imageinfo[0]) {
                $srcx = $imageinfo[0] - $srcw;
            }
        }

        // The resulting canvas that we will end up with is the destination height and width.
        $canvas = imagecreatetruecolor($dstw, $dsth);

        // Create a transparent canvas.
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagesavealpha($canvas, true);

        // Lets crop!
        imagesetinterpolation($canvas, IMG_BICUBIC);
        imagesetinterpolation($original, IMG_BICUBIC);

        imagecopyresampled($canvas, $original, $dstx, $dsty, $srcx, $srcy, $dstw, $dsth, $srcw, $srch);

        // Write the canvas to the temporary file.
        imagepng($canvas, $tmpfile, 9);

        // Cleanup.
        imagedestroy($original);
        imagedestroy($canvas);
    }

    public static function render_style($courseid = null) {
        global $PAGE, $COURSE;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }

        $width = get_config('local_banner', 'width');
        $height = get_config('local_banner', 'height');

        $r = $PAGE->get_renderer('local_banner');
        $html = $r->render_style($courseid, $width, $height);
        return $html;
    }

    public static function render_og_metadata() {
        global $PAGE, $COURSE, $SITE;

        $url = $PAGE->url->out();
        $fullname = $SITE->fullname;
        $summary = $COURSE->summary;

        $r = $PAGE->get_renderer('local_banner');
        $html = $r->render_og_metadata($COURSE->id, $url, $fullname, $summary);
        return $html;
    }

    public static function render_edit_buttons($banner) {
        global $PAGE, $COURSE, $USER;

        // Only allow modification of banners in the course context.
        if ($PAGE->context->contextlevel != CONTEXT_COURSE) {
            return '';
        }

        if (isset($USER->editing) && $USER->editing) {
            $r = $PAGE->get_renderer('local_banner');
            $html = $r->render_edit_buttons($COURSE->id, $banner, sesskey());
            return $html;
        }
    }

    public static function render_banner() {
        global $COURSE;

        $banner = self::load_from_courseid($COURSE->id);
        if (empty($banner)) {
            // If the default banner has been set, we will render it.
            $defaultbanner = get_config('local_banner', 'defaultbanner');
            if (!empty($defaultbanner)) {
                $html = self::render_style(self::BANNER_PLACEHOLDER);
            } else {
                $html = '';
            }

        } else {
            // Render the banner that was found for this course.
            $html = self::render_style();
        }

        $html .= self::render_edit_buttons($banner);

        return $html;
    }
}