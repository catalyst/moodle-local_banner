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
 * local banner plugin uninstall script.
 *
 * @package    local_banner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook called just before the plugin is uninstalled. Removes db entries and files from filesystem.
 *
 * @return bool Whether the function was successful.
 */
function xmldb_local_banner_uninstall() {
    global $CFG, $DB;

    // Get all files from mdl_files where component is local_banner.
    $banners = $DB->get_records('files', array('component' => 'local_banner'), '', 'id, contextid, itemid');
    
    foreach($banners as $banner) {
        // Delete the banner files for this course.
        $fs = get_file_storage();
        $fs->delete_area_files($banner->contextid, 'local_banner', 'banners', $banner->itemid);
    }

    return true;
}
