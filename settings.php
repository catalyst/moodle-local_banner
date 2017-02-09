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

    $defaultbanner = new admin_setting_configstoredfile(
        'local_banner/defaultbanner',
        get_string('defaultbanner',      'local_banner'),
        get_string('defaultbanner_desc', 'local_banner'),
        'placeholder'
    );

    $settings->add($heading);
    $settings->add($defaultbanner);
}
