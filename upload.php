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
 * Banner generation.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $PAGE;

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login();

if (!has_capability('moodle/course:update', $coursecontext)) {
    die();
}

$PAGE->requires->js_call_amd('local_banner/crop', 'cropper');

$url = new moodle_url('/local/banner/banner.php');
$PAGE->set_url($url);

$PAGE->set_context(context_system::instance()); // TODO: Course context.
$PAGE->set_pagelayout('standard');

$params = array();
$mform = new \local_banner\form\upload(null, $params);

if ($mform->is_cancelled()) {

} else if ($data = $mform->get_data()) {

} else {
    $data = new stdClass();
    $data->id = $id;

    $mform->set_data($data);
}

echo $OUTPUT->header();

echo $mform->display();

echo $OUTPUT->footer();