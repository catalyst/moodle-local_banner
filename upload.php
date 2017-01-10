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

require_once(__DIR__ . '/../../config.php');

global $PAGE, $DB;

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login();

if (!has_capability('moodle/course:update', $coursecontext)) {
    die();
}

$posturl = new moodle_url('/local/banner/process.php', array('id' => $id));
$url = new moodle_url('/local/banner/upload.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('standard');

$mform = new \local_banner\form\upload();

if ($mform->is_cancelled()) {

} else if ($data = $mform->get_data()) {
    $draftitemid = file_get_submitted_draft_itemid('banners');
    file_save_draft_area_files($draftitemid, $coursecontext->id, 'local_banner', 'banners', $id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($coursecontext->id, 'local_banner', 'banners', $id);

    $record = $DB->get_record('local_banner', array('course' => $id));
    if (empty($record)) {
        $record = new stdClass();
    }

    // We have set the file upload element to allow one file. This will iterate over the directory '.' and the file.
    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }

        if (empty($record->id)) {
            $record->course = $id;
            $record->file = $file->get_id();
            $DB->insert_record('local_banner', $record);
        } else {
            $record->file = $file->get_id();
            $DB->update_record('local_banner', $record);
        }

    }

    redirect($posturl);

} else {
    $draftitemid = file_get_submitted_draft_itemid('banners');
    file_prepare_draft_area($draftitemid, $coursecontext->id, 'local_banner', 'banners', $id);

    $data = new stdClass();
    $data->id = $id;
    $data->banners = $draftitemid;

    $mform->set_data($data);
}

echo $OUTPUT->header();

echo $mform->display();

echo $OUTPUT->footer();
