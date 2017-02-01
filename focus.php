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
 * Banner crop processing.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_banner\banner;

require_once(__DIR__ . '/../../config.php');

global $PAGE, $DB;

$id = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
$banner = banner::load_from_courseid($course->id);

require_login($course);
require_sesskey();

$url = new moodle_url('/local/banner/process.php', array('course' => $course->id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');

$PAGE->requires->css('/local/banner/css/cropper.css');

$fs = get_file_storage();

// Obtain the original uploaded file.
$file = $fs->get_file_by_id($banner->file);

$fileurl = moodle_url::make_pluginfile_url(
    $file->get_contextid(),
    $file->get_component(),
    $file->get_filearea(),
    $file->get_itemid(),
    $file->get_filepath(),
    $file->get_filename()
);

$mform = new \local_banner\form\process();

if ($mform->is_cancelled()) {

} else if ($data = $mform->get_data()) {
    // Upon submission of the focal point, update the top left crop x/y.
    $banner->invalidate_banner();
    $banner->set_data($data);
    $banner->save();

} else {
    $mform->set_data($banner);
}

// These parameters match the object used with cropper.js functions getData/setData.
$params = array(
    array('banner' => $banner, 'config' => '1'),
);

$PAGE->requires->js_call_amd('local_banner/crop', 'cropper', $params);

echo $OUTPUT->header();

$img = html_writer::img($fileurl, '', array('id' => 'bannerimage'));
echo html_writer::div($img);
echo html_writer::empty_tag('br');

echo $mform->display();

echo $OUTPUT->footer();
