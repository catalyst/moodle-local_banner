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

use local_banner\banner;

require_once(__DIR__ . '/../../config.php');

$id = optional_param('course', null, PARAM_INT);
$width = optional_param('width', 0, PARAM_INT);
$height = optional_param('height', 0, PARAM_INT);
$original = optional_param('original', 0, PARAM_INT);

if (empty($id)) {
    die();
}

$banner = banner::generate($id, $width, $height, $original);

if ($banner) {
    send_stored_file($banner, 0, 0, false);
}


