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
 * Banner uploading.
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

$posturl = new moodle_url('/local/banner/focus.php', array('course' => $course->id, 'sesskey' => sesskey()));
$url = new moodle_url('/local/banner/upload.php', array('course' => $course->id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');

$mform = new \local_banner\form\upload();

if ($mform->is_cancelled()) {

} else if ($data = $mform->get_data()) {
    $draftitemid = file_get_submitted_draft_itemid('banners');
    file_save_draft_area_files($draftitemid, $coursecontext->id, 'local_banner', 'banners', banner::BANNER_DEFAULT);

    $fs = get_file_storage();
    $files = $fs->get_area_files($coursecontext->id, 'local_banner', 'banners', banner::BANNER_DEFAULT);

    $banner = banner::load_from_courseid($course->id);
    if ($banner === null) {
        $banner = new banner();
    }

    // We have set the file upload element to allow one file. This will iterate over the directory '.' and the file.
    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }

        $banner->course = $course->id;
        $banner->context = $coursecontext->id;
        $banner->file = $file->get_id();
        $banner->filename = $file->get_filename();
        $banner->save();
        break;
    }

    redirect($posturl);

} else {
    $draftitemid = file_get_submitted_draft_itemid('banners');
    file_prepare_draft_area($draftitemid, $coursecontext->id, 'local_banner', 'banners', banner::BANNER_DEFAULT);

    $data = new stdClass();
    $data->course = $course->id;
    $data->banners = $draftitemid;

    $mform->set_data($data);
}

echo $OUTPUT->header();

echo $mform->display();

echo $OUTPUT->footer();
