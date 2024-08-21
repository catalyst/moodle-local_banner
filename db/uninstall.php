<?php
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
