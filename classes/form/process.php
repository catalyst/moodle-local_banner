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
 * Process crop form.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_banner\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to process the x/y crop dimensions.
 *
 * @package    local_banner
 * @author     Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process extends \moodleform {
    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'course');
        $mform->addElement('hidden', 'cropx');
        $mform->addElement('hidden', 'cropy');
        $mform->addElement('hidden', 'scalex');
        $mform->addElement('hidden', 'scaley');
        $mform->addElement('hidden', 'height');
        $mform->addElement('hidden', 'width');
        $mform->addElement('hidden', 'rotate');

        $mform->setType('id', PARAM_INT);
        $mform->setType('course', PARAM_INT);
        $mform->setType('cropx', PARAM_INT);
        $mform->setType('cropy', PARAM_INT);
        $mform->setType('scalex', PARAM_INT);
        $mform->setType('scaley', PARAM_INT);
        $mform->setType('height', PARAM_INT);
        $mform->setType('width', PARAM_INT);
        $mform->setType('rotate', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('setfocus', 'local_banner'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('returntocancel', 'local_banner'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Validate the parts of the request form for this module
     *
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array of error messages
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        return $errors;
    }
}
