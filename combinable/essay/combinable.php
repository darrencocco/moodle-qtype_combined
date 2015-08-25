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
 * Type base class that defines some basic properties
 * for the purpose of embedding the essay.
 * 
 * @author Darren Cocco <Darren.Cocco@surgeons.org>
 */
class qtype_combined_combinable_type_essay extends qtype_combined_combinable_type_base {
    
    /**
     * The question type that this embeds.
     * 
     * Used to find and call various methods and constructors
     * from that question.
     * 
     * @var string
     */
    protected $identifier = 'essay';
    
    /**
     * These are properties relating to the properties that need to
     * be or should be set for the embedded question but for which
     * you do not wish to present any options for.
     * 
     * @see qtype_combined_combinable_type_base::extra_question_properties()
     */
    protected function extra_question_properties() {
        return array('responserequired'=>false, 'attachmentsrequired'=>false, 'attachments'=>0);
    }
    
    /**
     * These are properties relating to the answers that need to be or
     * should be set for the embedded question but for which you do
     * not wish to present any options for.
     *  
     * @see qtype_combined_combinable_type_base::extra_answer_properties()
     */
    protected function extra_answer_properties() {
        return array();
    }
    
    /**
     * Defines the properties that the form fragment is going to return.
     * 
     * @see qtype_combined_combinable_type_base::subq_form_fragment_question_option_fields()
     */
    public function subq_form_fragment_question_option_fields() {
        return array(
            'responseformat' => null,
            'responsefieldlines' => null,
            'attachments' => null,
            'responsetemplate' => null,
            'graderinfo' => null
        );
    }
}

/**
 * Handles form controls for editing the essay subquestion.
 * 
 * @author Darren Cocco <Darren.Cocco@surgeons.org>
 */
class qtype_combined_combinable_essay extends qtype_combined_combinable_base {
    
    public $preferredBehaviour = 'manual';
        
    /**
     * Adds the controls to the quickform for the properties
     * that need to be set of the essay subquestion.
     * 
     * @param moodleform $combinedform
     * @param MoodleQuickForm $mform
     * @param boolean $repeatenabled
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $qtype = question_bank::get_qtype('essay');
        
        $mform->addElement('select', $this->form_field_name('responseformat'),
            get_string('responseformat', 'qtype_essay'), $qtype->response_formats());
        $mform->setDefault($this->form_field_name('responseformat'), 'editor');
        
        $mform->addElement('select', $this->form_field_name('responsefieldlines'),
            get_string('responsefieldlines', 'qtype_essay'), $qtype->response_sizes());
        $mform->setDefault($this->form_field_name('responsefieldlines'), 15);
        
        $mform->addElement('editor', $this->form_field_name('responsetemplate'), get_string('responsetemplate', 'qtype_essay'),
            array('rows' => 10),  array_merge($combinedform->editoroptions, array('maxfiles' => 0)));
        $mform->addHelpButton($this->form_field_name('responsetemplate'), 'responsetemplate', 'qtype_essay');
        $mform->addElement('editor', $this->form_field_name('graderinfo'), get_string('graderinfo', 'qtype_essay'),
            array('rows' => 10), $combinedform->editoroptions);
    }
    
    /**
     * Provides a string that outlines how to use
     * the subquestion in the question construction.
     * 
     * @see qtype_combined_combinable_base::code_construction_instructions()
     */
    protected function code_construction_instructions() {
        return 'Enter [#:essay] where # is a unique number on the page.';
    }
    
    /**
     * Validates the input data, I think.
     * 
     * No validation occurs currently.
     * 
     * @see qtype_combined_combinable_base::validate()
     */
    public function validate() {
        return array();
    }
    
    /**
     * Tricks the code into generating the right string for
     * search and replace for question rendering.
     * 
     * Despite the fact that no third parameter is used
     * the OU-Combined question type requires a third parameter
     * be defined in a sense. To that end one must return an
     * array of non-zero length otherwise the code will fail
     * to generate the appropriate string for finding the
     * location to render the subquestion inside the question.
     * 
     * @see qtype_combined_combinable_base::get_third_params()
     */
    public function get_third_params() {
        return [null];
    }
    
    /**
     * Overrides and calls parent function.
     * 
     * The purpose of this was to allow for the editor boxes to
     * work in the same manner as those presented in the base
     * essay question. As such this code is taken directly from
     * that question and tweaked to match the environment provided.
     * 
     * @see qtype_combined_combinable_base::data_to_form()
     */
    public function data_to_form($context, $fileoptions) {
        $formParams = parent::data_to_form($context, $fileoptions);
        
        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $formParams['graderinfo'] = array();
        $formParams['graderinfo']['text'] = file_prepare_draft_area(
            $draftid,           // Draftid
            $context->id, // context
            'qtype_essay',      // component
            'graderinfo',       // filarea
            !empty($formParams['id']) ? (int) $this->questionrec->id : null, // itemid
            $fileoptions, // options
            $this->questionrec->options->graderinfo // text.
        );
        $formParams['graderinfo']['format'] = $this->questionrec->options->graderinfoformat;
        $formParams['graderinfo']['itemid'] = $draftid;

        $formParams['responsetemplate'] = array(
            'text' => $this->questionrec->options->responsetemplate,
            'format' => $this->questionrec->options->responsetemplateformat,
        );
        
        return $formParams;
    }
    
    /**
     * This is pulled from essay/question.php.
     * @param moodle_page the page we are outputting to.
     * @return qtype_essay_format_renderer_base the response-format-specific renderer.
     */
    public function get_format_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_essay', 'format_' . $this->responseformat);
    }
}