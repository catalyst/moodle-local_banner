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

use moodle_url;

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

    public function __construct($data = null) {
        if (is_null($data)) {
            return;
        }

        $this->set_data($data);

        $this->adjust_field_types();
    }

    public function save() {
        global $DB;

        if (empty($this->id)) {
            return $DB->insert_record('local_banner', $this);
        } else {
            return $DB->update_record('local_banner', $this);
        }
    }

    public static function load_from_id($id) {
        global $DB;

        $data = $DB->get_record('local_banner', array('id' => $id));

        if ($data === false) {
            return null;
        }

        return new banner($data);
    }

    public static function load_from_courseid($courseid) {
        global $DB;

        $data = $DB->get_record('local_banner', array('course' => $courseid));

        // No file has been uploaded for the context of this course.
        if ($data === false) {
            return new banner(self::placeholder($courseid));
        }

        return new banner($data);
    }

    public function set_data($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    private static function placeholder($course) {
        $data = array(
            'context' => self::BANNER_PLACEHOLDER,
            'filename' => 'banner',
            'course' =>  $course,
        );

        return $data;
    }

    private function parse_ratio() {
        $config = get_config('local_banner', 'aspectratio');

        if ($exploded = explode(':', $config) || $exploded = explode('/', $config)) {
            return $exploded[0] / $exploded[1];
        }

        return $config;
    }

    /**
     * Converts the type of the fields as needed.
     */
    private function adjust_field_types() {
        // Adjust int fields.
        $fs = array('id', 'course', 'context', 'file', 'cropx', 'cropy', 'scalex', 'scaley', 'height', 'width', 'rotate');
        foreach ($fs as $f) {
            $this->$f = ($this->$f === null) ? null : (int)$this->$f;
        }
    }

    public static function generate($courseid, $itemid) {
        $banner = self::load_from_courseid($courseid);

        $fs = get_file_storage();

        // Try to obtain the file with the width $itemid.
        $file = $fs->get_area_files($banner->context, 'local_banner', 'banners', $itemid, 'itemid', false);

        // File does not exist. Create it.
        if(empty($file)) {
            return $banner->create_file($itemid);
        }

        return array_shift($file);
    }

    private function create_file($itemid) {
        $dir = make_request_directory();
        $tmpfile = tempnam($dir, 'banner_');

        $handle = fopen($tmpfile, "w");
        $this->image_crop($handle, $itemid);
        fclose($handle);

        // TODO: Temporarily returning. Lets not save anything to the database right now.
        return;

        $basename = pathinfo($this->filename, PATHINFO_FILENAME);
        $extension = pathinfo($this->filename, PATHINFO_EXTENSION);

        $record = array(
            'contextid' => $this->context,
            'component' => 'local_banner',
            'filearea' => 'banners',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $basename . '-' . $itemid . '.' . $extension,
        );

        $fs = get_file_storage();
        return $fs->create_file_from_pathname($record, $tmpfile);
    }

    private function image_crop($write, $itemid) {
        $fs = get_file_storage();
        $source = $fs->get_file_by_id($this->file);

        $imageinfo = @getimagesizefromstring($source->get_content());
        if (empty($imageinfo)) {
            return false;
        }

        // Create a new image from the file.
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

        $dst_x = 0; // x-coordinate of destination point.
        $dst_y = 0; // y-coordinate of destination point.
        $src_x = $this->cropx; // x-coordinate of source point.
        $src_y = $this->cropy; // y-coordinate of source point.
        $dst_w = $imageinfo[0]; // Destination width.
        $dst_h = $imageinfo[1]; // Destination height.
        $src_w = $imageinfo[0]; // Source width.
        $src_h = $imageinfo[1]; // Source height.

        // Lets crop!
        imagecopyresampled($canvas, $original, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        // TODO: Temp debugging output. Replace with sendfile of the saved content.
        header('Content-Type: image/png');
        imagepng($canvas, null, 9);

        imagedestroy($original);
        imagedestroy($canvas);
    }

    public static function render_style() {
        global $PAGE, $COURSE;
        $banner = self::load_from_courseid($COURSE->id);

        $r = $PAGE->get_renderer('local_banner');
        $html = $r->render_style($COURSE->id, $banner->cropx);
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

        if (isset($USER->editing) && $USER->editing) {
            $r = $PAGE->get_renderer('local_banner');
            $html = $r->render_edit_buttons($COURSE->id, sesskey());
            return $html;
        }
    }

    public static function render_banner() {
        return self::render_style() . self::render_edit_buttons();
    }
}