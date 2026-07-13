<?php

declare(strict_types=1);

/**
 * Employer portal routes (CSRF via employer group middleware).
 */

/** @var \JobVisa\App\Routing\RouteRegistrar|\App\Core\Router $router */

$router->get('/employer', 'Employer\\AiDashboardController@show');
$router->post('/employer/ai-dashboard/refresh', 'Employer\\AiDashboardController@refresh');

$router->get('/employer/recruiter-assistant', 'Employer\\RecruiterAssistantController@show');
$router->post('/employer/recruiter-assistant/search', 'Employer\\RecruiterAssistantController@search');
$router->post('/employer/recruiter-assistant/history/{historyId}/delete', 'Employer\\RecruiterAssistantController@deleteHistory');
$router->post('/employer/recruiter-assistant/history/clear', 'Employer\\RecruiterAssistantController@clearHistory');

$router->get('/employer/interview-assistant', 'Employer\\InterviewAssistantController@show');
$router->post('/employer/interview-assistant/generate', 'Employer\\InterviewAssistantController@generate');
$router->get('/employer/interview-assistant/sessions/{sessionId}', 'Employer\\InterviewAssistantController@session');
$router->post('/employer/interview-assistant/sessions/{sessionId}/scorecard', 'Employer\\InterviewAssistantController@saveScorecard');
$router->post('/employer/interview-assistant/sessions/{sessionId}/delete', 'Employer\\InterviewAssistantController@deleteHistory');
$router->post('/employer/interview-assistant/history/clear', 'Employer\\InterviewAssistantController@clearHistory');

$router->get('/employer/jobs', 'Employer\\ApplicantRankingController@jobs');
$router->get('/employer/jobs/{job}/applicants/ranking', 'Employer\\ApplicantRankingController@show');
$router->post('/employer/jobs/{job}/applicants/ranking/recalculate', 'Employer\\ApplicantRankingController@recalculate');
$router->get('/employer/jobs/{job}/applicants/ranking/history', 'Employer\\ApplicantRankingController@history');
$router->post('/employer/jobs/{job}/applicants/ranking/history/{historyId}/delete', 'Employer\\ApplicantRankingController@deleteHistory');
$router->post('/employer/jobs/{job}/applicants/ranking/history/clear', 'Employer\\ApplicantRankingController@clearHistory');
