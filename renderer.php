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
    public function render_style($courseid, $width, $height) {
        $params = array(
            'course' => $courseid,
            'width' => $width,
            'height' => $height,
        );
        $src = new moodle_url('/local/banner/', $params);

        $class = "<style>
@media (min-width: 992px) {
  .moodleheader {
    background-image: url('{$src->out(false)}');
  }
}
</style>";

        return $class;
    }

    public function render_edit_buttons($courseid, $banner, $sesskey) {
        global $OUTPUT;

        $params = array('course' => $courseid, 'sesskey' => $sesskey);

        $uploadurl = new moodle_url('/local/banner/upload.php', $params);
        $deleteurl = new moodle_url('/local/banner/delete.php', $params);
        $focusurl = new moodle_url('/local/banner/focus.php', $params);

        $uploadstring = get_string('replacebanner', 'local_banner');
        $deletestring = get_string('removebanner', 'local_banner');
        $focusstring = get_string('changefocus', 'local_banner');

        $uploadimg = $OUTPUT->pix_icon('t/up', $uploadstring, 'core', array('class' => 'iconsmall'));
        $deleteimg = $OUTPUT->pix_icon('t/delete', $deletestring, 'core', array('class' => 'iconsmall'));
        $focusimg = $OUTPUT->pix_icon('t/preview', $focusstring, 'core', array('class' => 'iconsmall'));

        $html  = html_writer::start_div('local_banner_buttons');

        if (!empty($banner)) {
            $html .= html_writer::link($focusurl, $focusimg . $focusstring);
        }

        $html .= html_writer::link($uploadurl, $uploadimg . $uploadstring);

        if (!empty($banner)) {
            $html .= html_writer::link($deleteurl, $deleteimg . $deletestring);
        }
        $html .= html_writer::end_div();

        return $html;
    }

    public function render_og_metadata($courseid, $url, $fullname, $summary) {
        $params = array('course' => $courseid);
        $src = new moodle_url('/local/banner/', $params);

        $data = array(
            'og:title' => $fullname,
            'og:type' => 'website',
            'og:url' => $url,
            'og:site_name' => $fullname,
            'og:description' => $summary,
            'og:image' => $src->out(),
            // 'og:image:width'
            // 'og:image:height'
            // 'og:image:type'
            // 'og:image:secure_url'
        );

        $html = '';

        foreach ($data as $property => $content) {
            $html .= html_writer::empty_tag('meta', array('property' => $property, 'content' => $content)) . PHP_EOL;
        }

        return $html;
    }
}
