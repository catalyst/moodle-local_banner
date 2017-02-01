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
 * Banner deletion.
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

require_login($course);
require_sesskey();

if (!has_capability('moodle/course:update', $coursecontext)) {
    die();
}

$posturl = new moodle_url('/course/view.php', array('id' => $course->id));
$url = new moodle_url('/local/banner/delete.php', array('course' => $course->id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');

$mform = new \local_banner\form\delete();

if ($mform->is_cancelled()) {
    redirect($posturl);

} else if ($data = $mform->get_data()) {
    $banner = banner::load_from_courseid($course->id);
    if (!empty($banner)) {
        $banner->delete();
    }

    redirect($posturl);
} else {
    $data = new stdClass();
    $data->course = $course->id;
    $mform->set_data($data);
}

echo $OUTPUT->header();

echo $OUTPUT->notification(get_string('deletefilenotification', 'local_banner'));

echo $mform->display();

echo $OUTPUT->footer();
