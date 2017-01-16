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
 *  local_banner plugin renderer
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


/**
 * Local Banner renderer class.
 *
 * @package    local_extension
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_banner_renderer extends plugin_renderer_base {

    /**
     * Renders the banner.
     *
     * @return string $out The html output.
     */
    public function render_banner($course) {
        $params = array('course' => $course);
        $src = new moodle_url('/local/banner/', $params);
        $img = html_writer::img($src, '');
        return $img;
    }

    public function render_og_metadata($course) {
        global $PAGE, $SITE;

        $params = array('course' => $course->id);
        $src = new moodle_url('/local/banner/', $params);

        $data = array(
            'og:title' => $course->fullname,
            'og:type' => 'website',
            'og:url' => $PAGE->url->out(),
            'og:site_name' => $SITE->fullname,
            'og:description' => $course->summary,
            'og:image' => $src->out(),
            // 'og:image:width'
            // 'og:image:height'
            // 'og:image:type'
            // 'og:image:secure_url'
        );

        $html = '';

        foreach($data as $property => $content) {
            $html .= html_writer::empty_tag('meta', array('property' => $property, 'content' => $content)) . PHP_EOL;
        }

        return $html;
    }
}
