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
        $pathinfo = pathinfo($defaultfilename);

        $file = $fs->get_file($context->id, 'local_banner', 'placeholder', 0, $pathinfo['dirname'], $pathinfo['basename']);

        // No placeholder has been found.
        if (empty($file)) {
            return null;
        }

        // Construct basic $data for a simple banner.
        $data = array(
            'context' => self::BANNER_PLACEHOLDER,
            'course' =>  self::BANNER_PLACEHOLDER,
        );

        return new banner($data);
    }

    private function parse_ratio() {
        $config = get_config('local_banner', 'aspectratio');

        preg_match('/[:]/', $config, $matches);

        $ratio_w = $config; // ratio_w could be a single integer in the config.
        $ratio_h = 1;       // default

        if ($matches) {
            $token = $matches[0];
            $exploded = explode($token, $config);

            if (count($exploded) == 2) {
                $ratio_w = $exploded[0];
                $ratio_h = $exploded[1];
            }
        }

        return array($ratio_w, $ratio_h);
    }

    public function get_ratio() {
        list($ratio_w, $ratio_h) = $this->parse_ratio();
        return $ratio_w / $ratio_h;
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
        if (empty($width) || empty($height)) {
            return null;
        }

        $banner->cropw = $width;
        $banner->croph = $height;
        $banner->itemid = $width . $height;

        if ($original) {
            $banner->itemid = banner::BANNER_DEFAULT;
        }

        /*
         * Try to obtain the file with the width $itemid.
         * The false argument prevents directory lookup.
         * When specifying $itemid this _should_ return an array of one element.
         */
        $filearray = $banner->fs->get_area_files($banner->context, 'local_banner', 'banners', $banner->itemid, 'itemid', false);
        $file = array_shift($filearray);

        // File does not exist. Create it.
        if(empty($file)) {

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
            } elseif ($file->get_itemid() != banner::BANNER_DEFAULT) {
                $file->delete();
            }
        }
    }

    private function create_file() {
        $dir = make_request_directory();
        $tmpfile = tempnam($dir, 'banner_');

        $handle = fopen($tmpfile, "w");
        $this->image_crop($tmpfile);
        fclose($handle);

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
        $dst_x = 0; // x-coordinate of destination point.
        $dst_y = 0; // y-coordinate of destination point.
        $src_x = $this->cropx; // x-coordinate of source point.
        $src_y = $this->cropy; // y-coordinate of source point.

        // Same as the input/output to keep the 1:1 scale.
        $dst_w = $imageinfo[0]; // Destination width.
        $dst_h = $imageinfo[1]; // Destination height.
        $src_w = $imageinfo[0]; // Source width.
        $src_h = $imageinfo[1]; // Source height.

        // URL parameters passed to the generation.
        $cropwidth = $this->cropw;
        $cropheight = $this->croph;

        $canvas = imagecreatetruecolor($cropwidth, $cropheight);

        // Create a transparent canvas.
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagesavealpha($canvas, true);

        $source_ratio = $src_w / $src_h;
        $target_ratio = $cropwidth / $cropheight;

        // If the source image is smaller than the canvas output.
        if ($src_w <= $cropwidth || $src_h <= $cropheight) {
            if ($target_ratio > $source_ratio) {
                $dst_w = $cropheight * $target_ratio;
            } else {
                $dst_h = $cropwidth / $target_ratio;
            }
        }

        // Find the center of the focus box.
        $focusx = $this->cropx + ($this->width / 2);
        $focusy = $this->cropy + ($this->height / 2);

        // Set the initial xy coordinates without disregarding any overflow.
        $src_x = $focusx - ($cropwidth / 2);
        $src_y = $focusy - ($cropheight / 2);

        // Checking the x overflow, left boundary.
        if ($focusx - ($cropwidth/2) < 0) {
            $src_x = 0;
        }

        // Checking the x overflow, right boundary.
        if ($focusx + ($cropwidth/2) > $dst_w) {
            $src_x = $dst_w - $cropwidth;
        }

        // Checking the y overflow, top boundary.
        if ($focusy - ($cropheight/2) < 0) {
            $src_y = 0;
        }

        // Checking the y overflow, bottom boundary.
        if ($focusy + ($cropheight/2) > $dst_h) {
            $src_y = $dst_h - $cropheight;
        }

        // Lets crop!
        imagesetinterpolation($canvas, IMG_BICUBIC);
        imagesetinterpolation($original, IMG_BICUBIC);

        //imagecopy($canvas, $original, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
        imagecopyresized($canvas, $original, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

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

        $r = $PAGE->get_renderer('local_banner');
        $html = $r->render_style($courseid);
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
                $html = self::render_style(banner::BANNER_PLACEHOLDER);
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