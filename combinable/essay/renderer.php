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
 * Combined-Embedded Essay question definition class.
 *
 * @package    qtype_combined
 * @subpackage essay
 * @copyright  2015 Royal Australasian College of Surgeons
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

/**
 * Handles rendering of the essay subquestion for preview/attempts/marking.
 *
 * @author Darren Cocco <Darren.Cocco@surgeons.org>
 */
class qtype_combined_essay_embedded_renderer implements qtype_combined_subquestion_renderer_interface {
    /**
     * Start of the HTML generation process for the essay subquestion.
     * 
     * @see qtype_combined_subquestion_renderer_interface::subquestion()
     */
    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq,
                                $placeno) {
        $question = $subq->question;
        $nameOfAnswer = $subq->step_data_name('answer');
        
        // Answer field.
        $step = $qa->get_last_step_with_qt_var($nameOfAnswer);
        
        if (!$step->has_qt_var($nameOfAnswer) && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array($nameOfAnswer=>$question->responsetemplate));
        }
        
        if (empty($options->readonly)) {
            $answer = $this->response_area_input($nameOfAnswer, $qa,
                $step, $question->responsefieldlines, $options->context);
        
        } else {
            $answer = $this->response_area_read_only($nameOfAnswer, $qa,
                $step, $question->responsefieldlines, $options->context);
        }
        
        $result = '';
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));
        $result .= html_writer::end_tag('div');
        
        return $result;
    }
    
    /**
     * Generates an editable field with the current response populated
     * in the editor.
     * 
     * @param string $name - Name of the answer field for the subquestion
     * @param question_attempt $qa - The question attempt for the parent question
     * @param question_attempt_step $step - The latest question step for the parent question
     * @param int $lines - Number of lines high the editor should be
     * @param context $context - The context that the parent question exists in
     * @return string
     */
    public function response_area_input($name, $qa, $step, $lines, $context) {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');
        
        $currentanswer = $step->get_qt_var($name);
        $inputname = $qa->get_qt_field_name($name);
        $responseformat = $step->get_qt_var($name . 'format');
        $id = $inputname . '_id';
        
        
        $editor = editors_get_preferred_editor($responseformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }
        
        list($draftitemid, $response) = $this->prepare_response_for_editing(
         $name, $step, $context);
        
        $editor->use_editor($id);
        
        $output = '';
        $output .= html_writer::start_tag('div', array('class' =>
            'qtype_combined_essay_response' . ' qtype_essay_response'));
        
        $output .= html_writer::tag('div', html_writer::tag('textarea', s($currentanswer),
            array('id' => $id, 'name' => $inputname, 'rows' => $lines, 'cols' => 60)));
        
        $output .= html_writer::start_tag('div');
        if (count($formats) == 1) {
            reset($formats);
            $output .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => $inputname . 'format', 'value' => key($formats)));
        
        } else {
            $output .= html_writer::label(get_string('format'), 'menu' . $inputname . 'format', false);
            $output .= ' ';
            $output .= html_writer::select($formats, $inputname . 'format', $responseformat, '');
        }
        $output .= html_writer::end_tag('div');
        
        $output .= html_writer::end_tag('div');
        return $output;
    }
    
    /**
     * Generates an readonly field with the current response populated
     * in the field.
     *
     * @param string $name - Name of the answer field for the subquestion
     * @param question_attempt $qa - The question attempt for the parent question
     * @param question_attempt_step $step - The latest question step for the parent question
     * @param int $lines - Number of lines high the editor should be
     * @param context $context - The context that the parent question exists in
     * @return string
     */
    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return html_writer::tag('div', $this->prepare_response($name, $qa, $step, $context),
            array('class' => 'qtype_combined_essay_response' . ' qtype_essay_response readonly'));
    }
    
    /**
     * Prepare the response for read-only display.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response($name, question_attempt $qa,
        question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }
    
        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'),
            $formatoptions);
    }
    
    /**
     * Prepare the response for editing.
     * @param string $name the variable name this input edits.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response_for_editing($name,
        question_attempt_step $step, $context) {
        return array(0, $step->get_qt_var($name));
    }
}