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
 * Defines the hooks necessary to make the numerical question type combinable
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/varnumericset/number_interpreter.php');

class qtype_combined_combinable_type_varnumeric extends qtype_combined_combinable_type_base {

    protected $identifier = 'numeric';

    protected function extra_question_properties() {
        return array('randomseed' => '', 'vartype' => array(0), 'varname' => array(''), 'variant' => array(''), 'novars' => 1);
    }

    protected function extra_answer_properties() {
        return array('sigfigs' => 0, 'fraction' => '1.0', 'feedback'  => array('text' => '', 'format' => FORMAT_PLAIN),
                        'checknumerical' => 0, 'checkscinotation' => 0, 'checkpowerof10' => 0, 'checkrounding' => 0,
                        'syserrorpenalty' => '0.0');
    }
    public function is_empty($subqformdata) {

        if (!empty($subqformdata->scinotation)) {
            return false;
        }
        foreach (array('answer', 'error') as $field) {
            if ('' !== trim($subqformdata->{$field}[0])) {
                return false;
            }
        }
        return parent::is_empty($subqformdata);
    }
}

class qtype_combined_combinable_varnumeric extends qtype_combined_combinable_accepts_width_specifier {

    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {

        $answergroupels = array();
        $answergroupels[] = $mform->createElement('text',
                                                 $this->field_name('answer[0]'),
                                                 get_string('answer', 'question'),
                                                 array('size' => 25));
        $answergroupels[] = $mform->createElement('text',
                                                 $this->field_name('error[0]'),
                                                 get_string('error', 'qtype_varnumericset'),
                                                 array('size' => 25));
        $mform->setType($this->field_name('answer'), PARAM_TEXT);
        $mform->setType($this->field_name('error'), PARAM_TEXT);
        $mform->addElement('group',
                           $this->field_name('answergroup'),
                           get_string('answer', 'question'),
                           $answergroupels,
                           '&nbsp;'.get_string('error', 'qtype_varnumericset'),
                           false);
        $mform->addElement('selectyesno', $this->field_name('scinotation'),
                           get_string('scinotation', 'qtype_combined'));
    }

    public function data_to_form($context, $fileoptions) {
        return parent::data_to_form($context, $fileoptions);
    }

    public function validate() {
        $errors = array();
        $interpret = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation(false);
        if ('' !== trim($this->formdata->error[0])) {
            if (!$interpret->match($this->formdata->error[0])) {
                $errors[$this->field_name('answergroup')] = get_string('err_notavalidnumberinerrortolerance', 'qtype_combined');
            }
        }
        if (!$interpret->match($this->formdata->answer[0])) {
            $errors[$this->field_name('answergroup')] = get_string('err_notavalidnumberinanswer', 'qtype_combined');
        }

        return $errors;
    }
}