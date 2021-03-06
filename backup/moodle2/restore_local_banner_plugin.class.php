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
 * Banner restore task
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class restore_local_banner_plugin extends restore_local_plugin {

    /**
     * Defines banner path in course/course.xml
     *
     * @return array
     */
    protected function define_course_plugin_structure() {
        $paths = array();
        $paths[] = new restore_path_element('banner', $this->get_pathfor(''));

        return $paths;
    }

    /**
     * Process banner information
     *
     * @param array $data information
     */
    public function process_banner($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->task->get_courseid();
        $data->context = $this->task->get_contextid();

        // Cleanup duplicate banners when merging content.
        $DB->delete_records('local_banner', array('course' => $data->course));

        $newitemid = $DB->insert_record('local_banner', $data);
        $this->set_mapping('banner', $oldid, $newitemid);
    }

    /**
     * Process the banner file
     */
    protected function after_execute_course() {
        global $DB;

        $this->add_related_files('local_banner', 'banners', null);

        $courseid = $this->task->get_courseid();
        $contextid = $this->task->get_contextid();

        // Remap the file to this new banner.
        $fs = get_file_storage();

        $params = array(
            'course' => $courseid,
            'context' => $contextid,
        );

        // Search for  files without specifying the itemid and ignore directories.
        $files = $fs->get_area_files($contextid, 'local_banner', 'banners', false, 'itemid', false);
        foreach ($files as $file) {
            $itemid = $file->get_itemid();

            if ($itemid === "0") {
                // This is the default uploaded file.
                $banner = $DB->get_record('local_banner', $params);
                $banner->file = $file->get_id();
                $DB->update_record('local_banner', $banner);
            }
        }

        $b = \local_banner\banner::load_from_courseid($courseid);
        if (!empty($b)) {
            $b->invalidate_banner();
        }
    }
}

