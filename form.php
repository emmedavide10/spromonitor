<?php
use tool_monitoring\Utility;

require_once __DIR__ . '/../../../config.php';

defined('MOODLE_INTERNAL') || die();

$utility = new utility();

$courseid = $utility->getCourseId();
$sproid = optional_param('sproid', 0, PARAM_INT);

// $selectedFields sarà ora un array contenente i valori selezionati
echo "sproid: " . $sproid;
echo "COURSE ID: " . $courseid;
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
$questions = $DB->get_records('surveypro_item', array('surveyproid' => $sproid));

/*
// Ordina l'array in modo decrescente in base all'attributo 'id'
usort($questions, function ($a, $b) {
    return $b->id - $a->id;
});*/

// Transform the $questions array to match the expected structure
$transformedQuestions = [];


foreach ($questions as $question) {
    // Assuming $question->plugin is available and represents the question type
    $isNumeric = ($question->plugin === 'numeric');
    $fieldDetails = $DB->get_record('surveyprofield_numeric', array('itemid' => $question->id));

    if ($question->plugin === 'numeric') {
        // Se è di tipo numeric, recupera i dettagli dalla tabella surveyprofield_numeric
        //$questionVariable = $fieldDetails->variable;
        $questionContent = $fieldDetails->content;

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
];
$utility->rendermustachefile('templates/templateparams.mustache', $data);

echo $OUTPUT->footer();

?>