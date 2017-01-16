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

class banner {
    public $id = null;

    public $course = null;

    public $file = null;

    public $cropx = null;

    public $cropy = null;

    public $scalex = null;

    public $scaley = null;

    public $height = null;

    public $width = null;

    public $rotate = null;

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

        if ($data === false) {
            return null;
        }

        return new banner($data);
    }

    private function set_data($data) {
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
        $fs = array('id', 'course', 'file', 'cropx', 'cropy', 'scalex', 'scaley', 'height', 'width', 'rotate');
        foreach ($fs as $f) {
            $this->$f = ($this->$f === null) ? null : (int)$this->$f;
        }
    }

    public static function generate($id) {
        $banner = self::load_from_courseid($id);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($banner->file);

        send_stored_file($file, DAYSECS, 0, false);
    }
}