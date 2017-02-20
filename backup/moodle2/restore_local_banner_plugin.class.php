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

class restore_local_banner_plugin extends restore_local_plugin {

    /**
     * Defines upload banner path in coure/course.xml
     *
     * @return array
     */
    protected function define_course_plugin_structure() {
        $paths = array();
        $paths[] = new restore_path_element('banner', $this->get_pathfor());

        return $paths;
    }

    /**
     * Process upload banner information
     *
     * @param array $data information
     */
    public function process_upload_banner($data) {
        // Map itemid from the backup to courseid of a new course.
        $this->set_mapping('itemid', $data['itemid'], $this->task->get_courseid(), true);
    }

    /**
     * Process the banner file
     */
    protected function after_execute_course() {
        $this->add_related_files('local_banner', 'banners', 'itemid');
    }
}

