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
 * Administration settings for local_banner
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once($CFG->dirroot.'/local/banner/lib.php');

    $settings = new admin_settingpage('local_banner', get_string('pluginname', 'local_banner'));

    $ADMIN->add('localplugins', $settings);

    $heading = new admin_setting_heading(
        'localbannersettings',
        new lang_string('pluginname', 'local_banner'),
        ''
    );

    $width = new admin_setting_configtext(
        'local_banner/width',
        get_string('width',      'local_banner'),
        get_string('width_desc', 'local_banner'),
        1000,
        PARAM_INT
    );

    $height = new admin_setting_configtext(
        'local_banner/height',
        get_string('height',      'local_banner'),
        get_string('height_desc', 'local_banner'),
        120,
        PARAM_INT
    );

    $ratio = new admin_setting_configtext(
        'local_banner/aspectratio',
        get_string('aspectratio',      'local_banner'),
        get_string('aspectratio_desc', 'local_banner'),
        '3/1',
        '/^\d+(\.\d+)?([:\/]+\d+)?$/'
        /*
        '/
        ^\d+         # Starts with digits,                            16
        (\.\d+)?     # Optional period followed by more digits        16.18
        ([:\/]+\d+)? # Optional period OR colon follow by more digits 16.18:10
        /x'         // PCRE_EXTENDED
        */
    );

    $defaultbanner = new admin_setting_configstoredfile(
        'local_banner/defaultbanner',
        get_string('defaultbanner',      'local_banner'),
        get_string('defaultbanner_desc', 'local_banner'),
        'placeholder'
    );

    $width->set_updatedcallback('local_banner_invalidate_callback');
    $height->set_updatedcallback('local_banner_invalidate_callback');
    $ratio->set_updatedcallback('local_banner_invalidate_callback');

    $settings->add($heading);
    $settings->add($width);
    $settings->add($height);
    $settings->add($ratio);
    $settings->add($defaultbanner);
}
