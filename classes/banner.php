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

    public function __construct($data = null) {
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
        return false;
    }

    public static function load_from_courseid($courseid) {
        global $DB;

        $data = $DB->get_record('local_banner', array('course' => $courseid));

        if ($data !== false) {
            return new banner($data);
        }

        // No record exists for $courseid.
        return false;
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
        $pathinfo = pathinfo($defaultfilename);

        $file = $fs->get_file($context->id, 'local_banner', 'placeholder', 0, $pathinfo['dirname'], $pathinfo['basename']);

        // Set default x/y coordinates.
        $imageinfo = @getimagesizefromstring($file->get_content());
        $canvaswidth = get_config('local_banner', 'width');
        $canvasheight = get_config('local_banner', 'height');
        $cropx = ($imageinfo[0] / 2) - ($canvaswidth / 2);
        $cropy = ($imageinfo[1] / 2) - ($canvasheight / 2);

        // No placeholder has been found.
        if (empty($file)) {
            return false;
        }

        // Construct basic $data for a simple banner.
        $data = array(
            'context' => self::BANNER_PLACEHOLDER,
            'course' =>  self::BANNER_PLACEHOLDER,
            /*
            'file' => $file->get_id(),
            'filename' =>  $pathinfo['basename'],
            'cropx' => $cropx,
            'cropy' => $cropy,
            */
        );

        return new banner($data);
    }

    private function parse_ratio() {
        $config = get_config('local_banner', 'aspectratio');

        preg_match('/[:\.]/', $config, $matches);

        if ($matches) {
            $token = $matches[0];
            $exploded = explode($token, $config);

            if (count($exploded) == 2) {
                return $exploded[0] / $exploded[1];
            }
        }

        return $config;
    }

    public static function generate($courseid, $width, $original) {
        // Check to see if a banner exists.
        $banner = self::load_from_courseid($courseid);

        if (empty($banner)) {
            $banner = self::load_placeholder();
        }

        // No placeholder has been found.
        if (empty($banner)) {
            return false;
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

        if ($original) {
            $itemid = banner::BANNER_DEFAULT;
        } elseif ($width) {
            // Allow generation of custom width banners.
            $itemid = $width;
        } else {
            // Obtain the course banner that has the itemid of the admin setting width.
            $itemid = get_config('local_banner', 'width');
        }

        /*
         * Try to obtain the file with the width $itemid.
         * The false argument prevents directory lookup.
         * When specifying $itemid this _should_ return an array of one element.
         */
        $filearray = $banner->fs->get_area_files($banner->context, 'local_banner', 'banners', $itemid, 'itemid', false);
        $file = array_shift($filearray);

        // File does not exist. Create it.
        if(empty($file)) {

            // No file has been saved for this course, return the default banner.
            if (empty($banner->file)) {
                $path = get_config('local_banner', 'defaultbanner');
                $file = $banner->fs->get_file(1, 'local_banner', 'placeholder', 0, '/', $path);

            } else {
                // This course has a banner fileid assigned to it. Lets create the banner / crop it again.
                $file = $banner->create_file($itemid);
            }

        }

        return $file;
    }

    public function invalidate_banner($purge = false) {
        $files = $this->fs->get_area_files($this->context, 'local_banner', 'banners', false, 'itemid', false);
        foreach ($files as $file) {
            if ($purge) {
                $file->delete();
            } elseif ($file->get_itemid() != banner::BANNER_DEFAULT) {
                $file->delete();
            }
        }
    }

    private function create_file($itemid) {
        $dir = make_request_directory();
        $tmpfile = tempnam($dir, 'banner_');

        $handle = fopen($tmpfile, "w");
        $this->image_crop($tmpfile);
        fclose($handle);

        $pathinfo = pathinfo($this->filename);

        $record = array(
            'contextid' => $this->context,
            'component' => 'local_banner',
            'filearea' => 'banners',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $pathinfo['filename'] . '-' . $itemid . '.' . $pathinfo['extension'],
            'mimetype' => get_mimetype_for_sending($pathinfo['basename']),
            'source' => $pathinfo['basename'],
        );

        return $this->fs->create_file_from_pathname($record, $tmpfile);
    }

    private function image_crop($tmpfile) {
        $source = $this->fs->get_file_by_id($this->file);

        if (empty($source)) {
            return false;
        }

        $imageinfo = @getimagesizefromstring($source->get_content());
        if (empty($imageinfo)) {
            return false;
        }

        // Create a new image from the file that we can process.
        $original = @imagecreatefromstring($source->get_content());
        if (empty($original)) {
            return false;
        }

        // The canvas we write to has the max width and height specified in the plugin config.
        $canvaswidth = get_config('local_banner', 'width');
        $canvasheight = get_config('local_banner', 'height');
        $canvas = imagecreatetruecolor($canvaswidth, $canvasheight);

        // Create a transparent canvas.
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagesavealpha($canvas, true);

        // Set deaults
        $dst_x = 0; // x-coordinate of destination point.
        $dst_y = 0; // y-coordinate of destination point.
        $src_x = $this->cropx; // x-coordinate of source point.
        $src_y = $this->cropy; // y-coordinate of source point.
        $dst_w = $imageinfo[0]; // Destination width.
        $dst_h = $imageinfo[1]; // Destination height.
        $src_w = $imageinfo[0]; // Source width.
        $src_h = $imageinfo[1]; // Source height.

        // Find the center of the focus box.
        $focusx = $this->cropx + ($this->width / 2);
        $focusy = $this->cropy + ($this->height / 2);

        // Set src_x to be the center of the focus box
        $src_x = $focusx;

        // Check/set the x overflow value for the left of the canvas.
        if (($focusx - ($canvaswidth /2)) < $imageinfo[0]) {
            $src_x = 0;
        }

        // Check/set the x overflow value for the right of the canvas.
        if (($focusx + ($canvaswidth /2)) > $imageinfo[0]) {
            $lx = $imageinfo[0] - $focusx;
            $src_y = ($focusx + $lx) - $canvaswidth;
        }

        // Set src_y to be the center of the focus box
        $src_y = $focusy;
        // Check/set the y overflow value for the top of the canvas.
        if (($focusy - ($canvasheight /2)) < $imageinfo[1]) {
            $src_y = 0;
        }

        // Check/set the y overflow value for the bottom of the canvas.
        if (($focusy + ($canvasheight /2)) > $imageinfo[1]) {
            $ly = $imageinfo[1] - $focusy;
            $src_y = ($focusy + $ly) - $canvasheight;
        }

        // Lets crop!
        imagecopyresampled($canvas, $original, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        // Write the canvas to the temporary file.
        imagepng($canvas, $tmpfile, 9);

        // Cleanup.
        imagedestroy($original);
        imagedestroy($canvas);
    }

    public static function render_style() {
        global $PAGE, $COURSE;

        $r = $PAGE->get_renderer('local_banner');
        $html = $r->render_style($COURSE->id);
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

    public static function render_edit_buttons() {
        global $PAGE, $COURSE, $USER;

        // Only allow modification of banners in the course context.
        if ($PAGE->context->contextlevel != CONTEXT_COURSE) {
            return '';
        }

        $banner = self::load_from_courseid($COURSE->id);

        if (isset($USER->editing) && $USER->editing) {
            $r = $PAGE->get_renderer('local_banner');
            $html = $r->render_edit_buttons($COURSE->id, $banner, sesskey());
            return $html;
        }
    }

    public static function render_banner() {
        return self::render_style() . self::render_edit_buttons();
    }
}