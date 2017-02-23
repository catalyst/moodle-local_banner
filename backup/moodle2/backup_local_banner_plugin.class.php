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
 * Banner backup task.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class backup_local_banner_plugin extends backup_local_plugin {
    /**
     * Returns the format information to attach to the course element.
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_include_condition(), 'include');

        // Define each element separated.
        $banner = new backup_nested_element($this->get_recommended_name(), array('id'),
                                            array('course',
                                                  'context',
                                                  'file',
                                                  'filename',
                                                  'cropx',
                                                  'cropy',
                                                  'scalex',
                                                  'scaley',
                                                  'height',
                                                  'width',
                                                  'rotate'));

        // Build the structure.
        $plugin->add_child($banner);

        // Define sources.
        $courseid = backup_helper::is_sqlparam($this->get_setting_value(backup::VAR_COURSEID));
        $banner->set_source_table('local_banner', array('course' => $courseid));

        // Define id annotations.
        $banner->annotate_ids('courseid', 'course');

        // Define file annotations.
        $contextid = $this->get_setting_value(backup::VAR_CONTEXTID);
        $banner->annotate_files('local_banner', 'banners', null, $contextid);

        return $plugin;
    }

    /**
     * Returns a condition for whether we include this in the backup
     * or not. We do that based on if there is any files in the file area.
     *
     * @return array Condition array
     */
    protected function get_include_condition() {
        global $DB;

        $contextid = $this->get_setting_value(backup::VAR_CONTEXTID);

        $result = '';
        $params = array('component' => 'local_banner', 'contextid' => $contextid);
        $bannerexists = $DB->record_exists('files', $params);

        if (!empty($bannerexists)) {
            $result = 'include';
        }

        return array('sqlparam' => $result);
    }
}

