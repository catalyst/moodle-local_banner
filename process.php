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

require_once(__DIR__ . '/../../config.php');

global $PAGE, $DB;

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
$record = $DB->get_record('local_banner', array('course' => $id), '*', MUST_EXIST);

require_login();

$url = new moodle_url('/local/banner/process.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');

$PAGE->requires->css('/local/banner/css/cropper.css');

$fs = get_file_storage();

$file = $fs->get_file_by_id($record->file);

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
    $record->cropx = $data->cropx;
    $record->cropy = $data->cropy;
    $record->scalex = $data->scalex;
    $record->scaley = $data->scaley;
    $record->height = $data->height;
    $record->width = $data->width;
    $record->rotate = $data->rotate;
    $DB->update_record('local_banner', $record);

} else {
    $record->id = $id;
    $mform->set_data($record);
}

// These parameters match the object used with cropper.js functions getData/setData.
$params = array(array(
    'x'      => intval($record->cropx),
    'y'      => intval($record->cropy),
    'scaleX' => intval($record->scalex),
    'scaleY' => intval($record->scaley),
    'height' => intval($record->height),
    'width'  => intval($record->width),
    'rotate' => intval($record->rotate),
));

$PAGE->requires->js_call_amd('local_banner/crop', 'cropper', $params);

echo $OUTPUT->header();

echo "<div><img src=\"$fileurl\" id='bannerimage' /></div><br />";

echo $mform->display();

echo $OUTPUT->footer();


