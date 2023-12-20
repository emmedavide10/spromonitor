<?php

use tool_monitoring\Utility;

require_once __DIR__ . '/../../../config.php';

defined('MOODLE_INTERNAL') || die();


$utility = new Utility();

$courseid = $utility->getCourseId();
$sproid = optional_param('sproid', 0, PARAM_INT);


$context = \context_course::instance($courseid);

$pagetitle = get_string('pagetitle', 'tool_monitoring');

$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/form.php', $paramsurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($pagetitle);
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    array('charts/chartjs/Chart.min', 'Chart'),
    array('exports' => 'Chart'),
    true
);

echo $OUTPUT->header();

$titleformspro = get_string('titleformspro', 'tool_monitoring');
$titleformparams = get_string('titleformparams', 'tool_monitoring');
$buttonsubmit = get_string('buttonsubmit', 'tool_monitoring');


$data = [];
$transformedSurveysName = [];

// Ottieni l'URL di provenienza
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// Inizializza una variabile booleana

// Verifica se l'URL di provenienza contiene "/moodle/mod/lti/"
if (strpos($referrer, '/moodle/mod/lti/') !== false) {

    $surveysname = $DB->get_records('surveypro', array('course' => $courseid));

    // Verifica se ci sono risultati
    if ($surveysname) {
        // Itera sui risultati e aggiungi all'array associativo
        foreach ($surveysname as $result) {
            $transformedSurveysName[] = [
                'id' => $result->id,
                'name' => $result->name,
                // Aggiungi altri campi se necessario
            ];
        }
    }

    $data = [
        'courseid' => $courseid,
        'namesurveys' => $transformedSurveysName,
        'buttonsubmit' => $buttonsubmit,
        'titleformspro' => $titleformspro
    ];
    $utility->rendermustachefile('templates/templatesurveys.mustache', $data);


} else{ 
    $questions = $DB->get_records('surveypro_item', array('surveyproid' => $sproid));

    // Transform the $questions array to match the expected structure
    $transformedQuestions = [];
    
    foreach ($questions as $question) {
        // Assuming $question->plugin is available and represents the question type
        $isNumeric = ($question->plugin === 'numeric');
        $fieldDetails = $DB->get_record('surveyprofield_numeric', array('itemid' => $question->id));
    
        if ($question->plugin === 'numeric') {
            // Se è di tipo numeric, recupera i dettagli dalla tabella surveyprofield_numeric
            $questionContent = $fieldDetails->variable;
            if(!isset($questionContent)){
                $questionContent = $fieldDetails->content;
            }
    
            $transformedQuestions[] = [
                'id' => $question->id,
                'questionContent' => $questionContent, // Adjust accordingly
                'isNumeric' => $isNumeric,
            ];
        }
    }
    // Now $transformedQuestions has the structure expected by your Mustache template
    
    $data = [
        'questions' => $transformedQuestions,
        'courseid' => $courseid,
        'sproid' => $sproid,
        'buttonsubmit' => $buttonsubmit,
        'titleformparams' => $titleformparams
    ];
    $utility->rendermustachefile('templates/templateparams.mustache', $data);
}


echo $OUTPUT->footer();
