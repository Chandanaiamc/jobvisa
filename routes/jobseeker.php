<?php

declare(strict_types=1);

/**
 * Job seeker portal + profile module routes.
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->get('/jobseeker', 'JobSeeker\\DashboardController@index');
$router->get('/jobseeker/profile', 'JobSeeker\\ProfileController@edit');
$router->post('/jobseeker/profile', 'JobSeeker\\ProfileController@update');
$router->post('/jobseeker/profile/avatar', 'JobSeeker\\ProfileController@uploadAvatar');

$router->get('/jobseeker/education', 'JobSeeker\\EducationController@index');
$router->post('/jobseeker/education', 'JobSeeker\\EducationController@store');
$router->post('/jobseeker/education/{id}', 'JobSeeker\\EducationController@update');
$router->post('/jobseeker/education/{id}/delete', 'JobSeeker\\EducationController@destroy');

$router->get('/jobseeker/experience', 'JobSeeker\\ExperienceController@index');
$router->post('/jobseeker/experience', 'JobSeeker\\ExperienceController@store');
$router->post('/jobseeker/experience/{id}', 'JobSeeker\\ExperienceController@update');
$router->post('/jobseeker/experience/{id}/delete', 'JobSeeker\\ExperienceController@destroy');

$router->get('/jobseeker/skills', 'JobSeeker\\SkillController@index');
$router->post('/jobseeker/skills', 'JobSeeker\\SkillController@store');
$router->post('/jobseeker/skills/{id}/delete', 'JobSeeker\\SkillController@destroy');

$router->get('/jobseeker/languages', 'JobSeeker\\LanguageController@index');
$router->post('/jobseeker/languages', 'JobSeeker\\LanguageController@store');
$router->post('/jobseeker/languages/{id}', 'JobSeeker\\LanguageController@update');
$router->post('/jobseeker/languages/{id}/delete', 'JobSeeker\\LanguageController@destroy');

$router->get('/jobseeker/cv', 'JobSeeker\\CvController@index');
$router->post('/jobseeker/cv', 'JobSeeker\\CvController@upload');
$router->get('/jobseeker/cv/download', 'JobSeeker\\CvController@download');
$router->post('/jobseeker/cv/delete', 'JobSeeker\\CvController@destroy');

$router->get('/jobseeker/settings', 'JobSeeker\\SettingsController@index');
$router->get('/jobseeker/media/avatar', 'JobSeeker\\MediaController@avatar');

// —— Resume foundation (Sprint 2D.1) — additive; existing CV routes unchanged ——
$router->get('/jobseeker/resumes', 'JobSeeker\\ResumeController@index');
$router->get('/jobseeker/resumes/create', 'JobSeeker\\ResumeController@create');
$router->post('/jobseeker/resumes', 'JobSeeker\\ResumeController@store');
$router->get('/jobseeker/resumes/{id}', 'JobSeeker\\ResumeController@show');
$router->get('/jobseeker/resumes/{id}/edit', 'JobSeeker\\ResumeController@edit');
$router->post('/jobseeker/resumes/{id}', 'JobSeeker\\ResumeController@update');
$router->post('/jobseeker/resumes/{id}/publish', 'JobSeeker\\ResumeController@publish');
$router->post('/jobseeker/resumes/{id}/draft', 'JobSeeker\\ResumeController@draft');
$router->post('/jobseeker/resumes/{id}/default', 'JobSeeker\\ResumeController@makeDefault');
$router->post('/jobseeker/resumes/{id}/delete', 'JobSeeker\\ResumeController@destroy');

// —— Resume personal information (Sprint 2D.2) ——
$router->get('/jobseeker/resumes/{id}/personal', 'JobSeeker\\ResumePersonalController@edit');
$router->post('/jobseeker/resumes/{id}/personal', 'JobSeeker\\ResumePersonalController@update');
$router->post('/jobseeker/resumes/{id}/photo', 'JobSeeker\\ResumePersonalController@uploadPhoto');
$router->post('/jobseeker/resumes/{id}/photo/delete', 'JobSeeker\\ResumePersonalController@deletePhoto');

// —— Resume professional summary (Sprint 2D.3) ——
$router->get('/jobseeker/resumes/{id}/professional', 'JobSeeker\\ResumeProfessionalController@edit');
$router->post('/jobseeker/resumes/{id}/professional', 'JobSeeker\\ResumeProfessionalController@update');
$router->post('/jobseeker/resumes/{id}/professional/autosave', 'JobSeeker\\ResumeProfessionalController@autosave');

// —— Resume education (Sprint 2D.4) — reuses `education` table ——
$router->get('/jobseeker/resumes/{id}/education', 'JobSeeker\\ResumeEducationController@index');
$router->post('/jobseeker/resumes/{id}/education', 'JobSeeker\\ResumeEducationController@store');
$router->post('/jobseeker/resumes/{id}/education/reorder', 'JobSeeker\\ResumeEducationController@reorder');
$router->get('/jobseeker/resumes/{id}/education/{education}/edit', 'JobSeeker\\ResumeEducationController@edit');
$router->post('/jobseeker/resumes/{id}/education/{education}', 'JobSeeker\\ResumeEducationController@update');
$router->post('/jobseeker/resumes/{id}/education/{education}/delete', 'JobSeeker\\ResumeEducationController@destroy');
$router->post('/jobseeker/resumes/{id}/education/{education}/restore', 'JobSeeker\\ResumeEducationController@restore');

// —— Resume work experience (Sprint 2D.5) — reuses `work_experience` table ——
$router->get('/jobseeker/resumes/{id}/experience', 'JobSeeker\\ResumeExperienceController@index');
$router->post('/jobseeker/resumes/{id}/experience', 'JobSeeker\\ResumeExperienceController@store');
$router->post('/jobseeker/resumes/{id}/experience/reorder', 'JobSeeker\\ResumeExperienceController@reorder');
$router->get('/jobseeker/resumes/{id}/experience/{experience}/edit', 'JobSeeker\\ResumeExperienceController@edit');
$router->post('/jobseeker/resumes/{id}/experience/{experience}', 'JobSeeker\\ResumeExperienceController@update');
$router->post('/jobseeker/resumes/{id}/experience/{experience}/delete', 'JobSeeker\\ResumeExperienceController@destroy');
$router->post('/jobseeker/resumes/{id}/experience/{experience}/restore', 'JobSeeker\\ResumeExperienceController@restore');

// —— Resume skills (Sprint 2D.6) — catalogue `skills`; not user_skills ——
$router->get('/jobseeker/resumes/{id}/skills', 'JobSeeker\\ResumeSkillController@index');
$router->get('/jobseeker/resumes/{id}/skills/search', 'JobSeeker\\ResumeSkillController@search');
$router->post('/jobseeker/resumes/{id}/skills', 'JobSeeker\\ResumeSkillController@store');
$router->post('/jobseeker/resumes/{id}/skills/reorder', 'JobSeeker\\ResumeSkillController@reorder');
$router->get('/jobseeker/resumes/{id}/skills/{skill}/edit', 'JobSeeker\\ResumeSkillController@edit');
$router->post('/jobseeker/resumes/{id}/skills/{skill}', 'JobSeeker\\ResumeSkillController@update');
$router->post('/jobseeker/resumes/{id}/skills/{skill}/delete', 'JobSeeker\\ResumeSkillController@destroy');
$router->post('/jobseeker/resumes/{id}/skills/{skill}/restore', 'JobSeeker\\ResumeSkillController@restore');

// —— Resume languages (Sprint 2D.7) — catalogue `languages`; not user_languages ——
$router->get('/jobseeker/resumes/{id}/languages', 'JobSeeker\\ResumeLanguageController@index');
$router->get('/jobseeker/resumes/{id}/languages/search', 'JobSeeker\\ResumeLanguageController@search');
$router->post('/jobseeker/resumes/{id}/languages', 'JobSeeker\\ResumeLanguageController@store');
$router->post('/jobseeker/resumes/{id}/languages/reorder', 'JobSeeker\\ResumeLanguageController@reorder');
$router->get('/jobseeker/resumes/{id}/languages/{language}/edit', 'JobSeeker\\ResumeLanguageController@edit');
$router->post('/jobseeker/resumes/{id}/languages/{language}', 'JobSeeker\\ResumeLanguageController@update');
$router->post('/jobseeker/resumes/{id}/languages/{language}/delete', 'JobSeeker\\ResumeLanguageController@destroy');
$router->post('/jobseeker/resumes/{id}/languages/{language}/restore', 'JobSeeker\\ResumeLanguageController@restore');
$router->post('/jobseeker/resumes/{id}/languages/{language}/certificate', 'JobSeeker\\ResumeLanguageController@uploadCertificate');
$router->post('/jobseeker/resumes/{id}/languages/{language}/certificate/delete', 'JobSeeker\\ResumeLanguageController@deleteCertificate');
$router->get('/jobseeker/resumes/{id}/languages/{language}/certificate/download', 'JobSeeker\\ResumeLanguageController@downloadCertificate');

// —— Resume certifications & licences (Sprint 2D.8) — resume-scoped; not profile ——
$router->get('/jobseeker/resumes/{id}/certifications', 'JobSeeker\\ResumeCertificationController@index');
$router->post('/jobseeker/resumes/{id}/certifications', 'JobSeeker\\ResumeCertificationController@store');
$router->post('/jobseeker/resumes/{id}/certifications/reorder', 'JobSeeker\\ResumeCertificationController@reorder');
$router->get('/jobseeker/resumes/{id}/certifications/{certification}/edit', 'JobSeeker\\ResumeCertificationController@edit');
$router->post('/jobseeker/resumes/{id}/certifications/{certification}', 'JobSeeker\\ResumeCertificationController@update');
$router->post('/jobseeker/resumes/{id}/certifications/{certification}/delete', 'JobSeeker\\ResumeCertificationController@destroy');
$router->post('/jobseeker/resumes/{id}/certifications/{certification}/restore', 'JobSeeker\\ResumeCertificationController@restore');
$router->post('/jobseeker/resumes/{id}/certifications/{certification}/certificate', 'JobSeeker\\ResumeCertificationController@uploadCertificate');
$router->post('/jobseeker/resumes/{id}/certifications/{certification}/certificate/delete', 'JobSeeker\\ResumeCertificationController@deleteCertificate');
$router->get('/jobseeker/resumes/{id}/certifications/{certification}/certificate/download', 'JobSeeker\\ResumeCertificationController@downloadCertificate');

// —— Resume projects & portfolio (Sprint 2D.9) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/projects', 'JobSeeker\\ResumeProjectController@index');
$router->post('/jobseeker/resumes/{id}/projects', 'JobSeeker\\ResumeProjectController@store');
$router->post('/jobseeker/resumes/{id}/projects/reorder', 'JobSeeker\\ResumeProjectController@reorder');
$router->get('/jobseeker/resumes/{id}/projects/{project}/edit', 'JobSeeker\\ResumeProjectController@edit');
$router->post('/jobseeker/resumes/{id}/projects/{project}', 'JobSeeker\\ResumeProjectController@update');
$router->post('/jobseeker/resumes/{id}/projects/{project}/delete', 'JobSeeker\\ResumeProjectController@destroy');
$router->post('/jobseeker/resumes/{id}/projects/{project}/restore', 'JobSeeker\\ResumeProjectController@restore');
$router->post('/jobseeker/resumes/{id}/projects/{project}/image', 'JobSeeker\\ResumeProjectController@uploadImage');
$router->post('/jobseeker/resumes/{id}/projects/{project}/image/delete', 'JobSeeker\\ResumeProjectController@deleteImage');
$router->get('/jobseeker/resumes/{id}/projects/{project}/image/download', 'JobSeeker\\ResumeProjectController@downloadImage');
$router->post('/jobseeker/resumes/{id}/projects/{project}/document', 'JobSeeker\\ResumeProjectController@uploadDocument');
$router->post('/jobseeker/resumes/{id}/projects/{project}/document/delete', 'JobSeeker\\ResumeProjectController@deleteDocument');
$router->get('/jobseeker/resumes/{id}/projects/{project}/document/download', 'JobSeeker\\ResumeProjectController@downloadDocument');

// —— Resume awards & achievements (Sprint 2E.1) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/achievements', 'JobSeeker\\ResumeAchievementController@index');
$router->get('/jobseeker/resumes/{id}/achievements/search', 'JobSeeker\\ResumeAchievementController@search');
$router->get('/jobseeker/resumes/{id}/achievements/cities', 'JobSeeker\\ResumeAchievementController@cities');
$router->post('/jobseeker/resumes/{id}/achievements', 'JobSeeker\\ResumeAchievementController@store');
$router->post('/jobseeker/resumes/{id}/achievements/reorder', 'JobSeeker\\ResumeAchievementController@reorder');
$router->get('/jobseeker/resumes/{id}/achievements/{achievement}/edit', 'JobSeeker\\ResumeAchievementController@edit');
$router->post('/jobseeker/resumes/{id}/achievements/{achievement}', 'JobSeeker\\ResumeAchievementController@update');
$router->post('/jobseeker/resumes/{id}/achievements/{achievement}/delete', 'JobSeeker\\ResumeAchievementController@destroy');
$router->post('/jobseeker/resumes/{id}/achievements/{achievement}/restore', 'JobSeeker\\ResumeAchievementController@restore');
$router->post('/jobseeker/resumes/{id}/achievements/{achievement}/certificate', 'JobSeeker\\ResumeAchievementController@uploadCertificate');
$router->post('/jobseeker/resumes/{id}/achievements/{achievement}/certificate/delete', 'JobSeeker\\ResumeAchievementController@deleteCertificate');
$router->get('/jobseeker/resumes/{id}/achievements/{achievement}/certificate/download', 'JobSeeker\\ResumeAchievementController@downloadCertificate');

// —— Resume publications & research (Sprint 2E.2) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/publications', 'JobSeeker\\ResumePublicationController@index');
$router->get('/jobseeker/resumes/{id}/publications/search', 'JobSeeker\\ResumePublicationController@search');
$router->get('/jobseeker/resumes/{id}/publications/cities', 'JobSeeker\\ResumePublicationController@cities');
$router->post('/jobseeker/resumes/{id}/publications', 'JobSeeker\\ResumePublicationController@store');
$router->post('/jobseeker/resumes/{id}/publications/reorder', 'JobSeeker\\ResumePublicationController@reorder');
$router->get('/jobseeker/resumes/{id}/publications/{publication}/edit', 'JobSeeker\\ResumePublicationController@edit');
$router->post('/jobseeker/resumes/{id}/publications/{publication}', 'JobSeeker\\ResumePublicationController@update');
$router->post('/jobseeker/resumes/{id}/publications/{publication}/delete', 'JobSeeker\\ResumePublicationController@destroy');
$router->post('/jobseeker/resumes/{id}/publications/{publication}/restore', 'JobSeeker\\ResumePublicationController@restore');
$router->post('/jobseeker/resumes/{id}/publications/{publication}/document', 'JobSeeker\\ResumePublicationController@uploadDocument');
$router->post('/jobseeker/resumes/{id}/publications/{publication}/remove-document', 'JobSeeker\\ResumePublicationController@removeDocument');
$router->get('/jobseeker/resumes/{id}/publications/{publication}/download', 'JobSeeker\\ResumePublicationController@download');

// —— Resume portfolio (Sprint 2E.3) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/portfolio', 'JobSeeker\\ResumePortfolioController@index');
$router->get('/jobseeker/resumes/{id}/portfolio/search', 'JobSeeker\\ResumePortfolioController@search');
$router->get('/jobseeker/resumes/{id}/portfolio/cities', 'JobSeeker\\ResumePortfolioController@cities');
$router->post('/jobseeker/resumes/{id}/portfolio', 'JobSeeker\\ResumePortfolioController@store');
$router->post('/jobseeker/resumes/{id}/portfolio/reorder', 'JobSeeker\\ResumePortfolioController@reorder');
$router->get('/jobseeker/resumes/{id}/portfolio/{portfolio}/edit', 'JobSeeker\\ResumePortfolioController@edit');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}', 'JobSeeker\\ResumePortfolioController@update');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/delete', 'JobSeeker\\ResumePortfolioController@destroy');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/restore', 'JobSeeker\\ResumePortfolioController@restore');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/featured', 'JobSeeker\\ResumePortfolioController@uploadFeatured');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/remove-featured', 'JobSeeker\\ResumePortfolioController@removeFeatured');
$router->get('/jobseeker/resumes/{id}/portfolio/{portfolio}/download-featured', 'JobSeeker\\ResumePortfolioController@downloadFeatured');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/gallery', 'JobSeeker\\ResumePortfolioController@uploadGallery');
$router->post('/jobseeker/resumes/{id}/portfolio/{portfolio}/gallery/{image}/delete', 'JobSeeker\\ResumePortfolioController@removeGallery');

// —— Resume professional references (Sprint 2E.4) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/references', 'JobSeeker\\ResumeReferenceController@index');
$router->get('/jobseeker/resumes/{id}/references/search', 'JobSeeker\\ResumeReferenceController@search');
$router->get('/jobseeker/resumes/{id}/references/cities', 'JobSeeker\\ResumeReferenceController@cities');
$router->post('/jobseeker/resumes/{id}/references', 'JobSeeker\\ResumeReferenceController@store');
$router->post('/jobseeker/resumes/{id}/references/reorder', 'JobSeeker\\ResumeReferenceController@reorder');
$router->get('/jobseeker/resumes/{id}/references/{reference}/edit', 'JobSeeker\\ResumeReferenceController@edit');
$router->post('/jobseeker/resumes/{id}/references/{reference}', 'JobSeeker\\ResumeReferenceController@update');
$router->post('/jobseeker/resumes/{id}/references/{reference}/delete', 'JobSeeker\\ResumeReferenceController@destroy');
$router->post('/jobseeker/resumes/{id}/references/{reference}/restore', 'JobSeeker\\ResumeReferenceController@restore');

// —— Resume intelligence (Sprint 2F.1) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/intelligence', 'JobSeeker\\ResumeIntelligenceController@show');
$router->post('/jobseeker/resumes/{id}/intelligence/recalculate', 'JobSeeker\\ResumeIntelligenceController@recalculate');
$router->post('/jobseeker/resumes/{id}/intelligence/history/{historyId}/delete', 'JobSeeker\\ResumeIntelligenceController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/intelligence/history/clear', 'JobSeeker\\ResumeIntelligenceController@clearHistory');

// —— Career Coach (Sprint 2F.8) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/career-coach', 'JobSeeker\\CareerCoachController@show');
$router->post('/jobseeker/resumes/{id}/career-coach/recalculate', 'JobSeeker\\CareerCoachController@recalculate');
$router->post('/jobseeker/resumes/{id}/career-coach/history/{historyId}/delete', 'JobSeeker\\CareerCoachController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/career-coach/history/{historyId}/restore', 'JobSeeker\\CareerCoachController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/career-coach/history/clear', 'JobSeeker\\CareerCoachController@clearHistory');

// —— AI Resume Builder (Sprint 2F.9) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/ai-builder', 'JobSeeker\\ResumeBuilderController@show');
$router->post('/jobseeker/resumes/{id}/ai-builder/generate', 'JobSeeker\\ResumeBuilderController@generate');
$router->post('/jobseeker/resumes/{id}/ai-builder/regenerate', 'JobSeeker\\ResumeBuilderController@regenerate');
$router->post('/jobseeker/resumes/{id}/ai-builder/versions/{versionId}/save', 'JobSeeker\\ResumeBuilderController@saveVersion');
$router->post('/jobseeker/resumes/{id}/ai-builder/versions/{versionId}/activate', 'JobSeeker\\ResumeBuilderController@activateVersion');
$router->post('/jobseeker/resumes/{id}/ai-builder/versions/{versionId}/delete', 'JobSeeker\\ResumeBuilderController@deleteVersion');
$router->post('/jobseeker/resumes/{id}/ai-builder/history/{historyId}/delete', 'JobSeeker\\ResumeBuilderController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/ai-builder/history/{historyId}/restore', 'JobSeeker\\ResumeBuilderController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/ai-builder/history/clear', 'JobSeeker\\ResumeBuilderController@clearHistory');

// —— AI Cover Letter Generator (Sprint 3.1) — resume-scoped ——
$router->get('/jobseeker/resumes/{id}/cover-letters', 'JobSeeker\\CoverLetterController@show');
$router->post('/jobseeker/resumes/{id}/cover-letters/generate', 'JobSeeker\\CoverLetterController@generate');
$router->post('/jobseeker/resumes/{id}/cover-letters/regenerate', 'JobSeeker\\CoverLetterController@regenerate');
$router->post('/jobseeker/resumes/{id}/cover-letters/versions/{versionId}/save', 'JobSeeker\\CoverLetterController@saveVersion');
$router->post('/jobseeker/resumes/{id}/cover-letters/versions/{versionId}/delete', 'JobSeeker\\CoverLetterController@deleteVersion');
$router->get('/jobseeker/resumes/{id}/cover-letters/versions/{versionId}/export/pdf', 'JobSeeker\\CoverLetterController@exportPdf');
$router->get('/jobseeker/resumes/{id}/cover-letters/versions/{versionId}/export/docx', 'JobSeeker\\CoverLetterController@exportDocx');
$router->post('/jobseeker/resumes/{id}/cover-letters/history/{historyId}/delete', 'JobSeeker\\CoverLetterController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/cover-letters/history/{historyId}/restore', 'JobSeeker\\CoverLetterController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/cover-letters/history/{historyId}/purge', 'JobSeeker\\CoverLetterController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/cover-letters/history/clear', 'JobSeeker\\CoverLetterController@clearHistory');

// —— AI Salary Intelligence (Sprint 3.3) ——
$router->get('/jobseeker/resumes/{id}/salary-intelligence', 'JobSeeker\\SalaryIntelligenceController@show');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/calculate', 'JobSeeker\\SalaryIntelligenceController@calculate');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/recalculate', 'JobSeeker\\SalaryIntelligenceController@recalculate');
$router->get('/jobseeker/resumes/{id}/salary-intelligence/history', 'JobSeeker\\SalaryIntelligenceController@history');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/history/{historyId}/delete', 'JobSeeker\\SalaryIntelligenceController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/history/{historyId}/restore', 'JobSeeker\\SalaryIntelligenceController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/history/{historyId}/purge', 'JobSeeker\\SalaryIntelligenceController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/salary-intelligence/history/clear', 'JobSeeker\\SalaryIntelligenceController@clearHistory');
$router->get('/jobseeker/resumes/{id}/salary-intelligence/predictions/{predictionId}/export/pdf', 'JobSeeker\\SalaryIntelligenceController@exportPdf');

// —— AI Skill Gap Analyzer (Sprint 3.4) ——
$router->get('/jobseeker/resumes/{id}/skill-gap', 'JobSeeker\\SkillGapController@show');
$router->post('/jobseeker/resumes/{id}/skill-gap/analyze', 'JobSeeker\\SkillGapController@analyze');
$router->post('/jobseeker/resumes/{id}/skill-gap/recalculate', 'JobSeeker\\SkillGapController@recalculate');
$router->get('/jobseeker/resumes/{id}/skill-gap/history', 'JobSeeker\\SkillGapController@history');
$router->post('/jobseeker/resumes/{id}/skill-gap/history/{historyId}/delete', 'JobSeeker\\SkillGapController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/skill-gap/history/{historyId}/restore', 'JobSeeker\\SkillGapController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/skill-gap/history/{historyId}/purge', 'JobSeeker\\SkillGapController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/skill-gap/history/clear', 'JobSeeker\\SkillGapController@clearHistory');
$router->get('/jobseeker/resumes/{id}/skill-gap/analyses/{analysisId}/export/pdf', 'JobSeeker\\SkillGapController@exportPdf');

// —— AI Learning Path Generator (Sprint 3.5) ——
$router->get('/jobseeker/resumes/{id}/learning-path', 'JobSeeker\\LearningPathController@show');
$router->post('/jobseeker/resumes/{id}/learning-path/generate', 'JobSeeker\\LearningPathController@generate');
$router->post('/jobseeker/resumes/{id}/learning-path/recalculate', 'JobSeeker\\LearningPathController@recalculate');
$router->post('/jobseeker/resumes/{id}/learning-path/paths/{pathId}/milestones', 'JobSeeker\\LearningPathController@completeMilestone');
$router->get('/jobseeker/resumes/{id}/learning-path/history', 'JobSeeker\\LearningPathController@history');
$router->post('/jobseeker/resumes/{id}/learning-path/history/{historyId}/delete', 'JobSeeker\\LearningPathController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/learning-path/history/{historyId}/restore', 'JobSeeker\\LearningPathController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/learning-path/history/{historyId}/purge', 'JobSeeker\\LearningPathController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/learning-path/history/clear', 'JobSeeker\\LearningPathController@clearHistory');
$router->get('/jobseeker/resumes/{id}/learning-path/paths/{pathId}/export/pdf', 'JobSeeker\\LearningPathController@exportPdf');

// —— AI Portfolio & Project Builder (Sprint 3.6) ——
$router->get('/jobseeker/resumes/{id}/portfolio-builder', 'JobSeeker\\PortfolioBuilderController@show');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/generate', 'JobSeeker\\PortfolioBuilderController@generate');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/recalculate', 'JobSeeker\\PortfolioBuilderController@recalculate');
$router->get('/jobseeker/resumes/{id}/portfolio-builder/history', 'JobSeeker\\PortfolioBuilderController@history');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/history/{historyId}/delete', 'JobSeeker\\PortfolioBuilderController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/history/{historyId}/restore', 'JobSeeker\\PortfolioBuilderController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/history/{historyId}/purge', 'JobSeeker\\PortfolioBuilderController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/portfolio-builder/history/clear', 'JobSeeker\\PortfolioBuilderController@clearHistory');
$router->get('/jobseeker/resumes/{id}/portfolio-builder/plans/{planId}/export/pdf', 'JobSeeker\\PortfolioBuilderController@exportPdf');

$router->get('/jobseeker/resumes/{id}/mock-interview', 'JobSeeker\\MockInterviewController@show');
$router->post('/jobseeker/resumes/{id}/mock-interview/generate', 'JobSeeker\\MockInterviewController@generate');
$router->post('/jobseeker/resumes/{id}/mock-interview/analyze', 'JobSeeker\\MockInterviewController@analyze');
$router->post('/jobseeker/resumes/{id}/mock-interview/recalculate', 'JobSeeker\\MockInterviewController@recalculate');
$router->get('/jobseeker/resumes/{id}/mock-interview/history', 'JobSeeker\\MockInterviewController@history');
$router->post('/jobseeker/resumes/{id}/mock-interview/history/{historyId}/delete', 'JobSeeker\\MockInterviewController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/mock-interview/history/{historyId}/restore', 'JobSeeker\\MockInterviewController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/mock-interview/history/{historyId}/purge', 'JobSeeker\\MockInterviewController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/mock-interview/history/clear', 'JobSeeker\\MockInterviewController@clearHistory');
$router->get('/jobseeker/resumes/{id}/mock-interview/sessions/{sessionId}/export/pdf', 'JobSeeker\\MockInterviewController@exportPdf');

// —— AI Job Search Copilot (Sprint 3.8) ——
$router->get('/jobseeker/resumes/{id}/job-search-copilot', 'JobSeeker\\JobSearchCopilotController@show');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/generate', 'JobSeeker\\JobSearchCopilotController@generate');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/recalculate', 'JobSeeker\\JobSearchCopilotController@recalculate');
$router->get('/jobseeker/resumes/{id}/job-search-copilot/history', 'JobSeeker\\JobSearchCopilotController@history');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/history/{historyId}/delete', 'JobSeeker\\JobSearchCopilotController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/history/{historyId}/restore', 'JobSeeker\\JobSearchCopilotController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/history/{historyId}/purge', 'JobSeeker\\JobSearchCopilotController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/job-search-copilot/history/clear', 'JobSeeker\\JobSearchCopilotController@clearHistory');
$router->get('/jobseeker/resumes/{id}/job-search-copilot/plans/{planId}/export/pdf', 'JobSeeker\\JobSearchCopilotController@exportPdf');

// —— AI Offer Evaluation Assistant (Sprint 3.9) ——
$router->get('/jobseeker/resumes/{id}/offer-evaluation', 'JobSeeker\\OfferEvaluationController@show');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/evaluate', 'JobSeeker\\OfferEvaluationController@evaluate');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/recalculate', 'JobSeeker\\OfferEvaluationController@recalculate');
$router->get('/jobseeker/resumes/{id}/offer-evaluation/history', 'JobSeeker\\OfferEvaluationController@history');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/history/{historyId}/delete', 'JobSeeker\\OfferEvaluationController@deleteHistory');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/history/{historyId}/restore', 'JobSeeker\\OfferEvaluationController@restoreHistory');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/history/{historyId}/purge', 'JobSeeker\\OfferEvaluationController@purgeHistory');
$router->post('/jobseeker/resumes/{id}/offer-evaluation/history/clear', 'JobSeeker\\OfferEvaluationController@clearHistory');
$router->get('/jobseeker/resumes/{id}/offer-evaluation/analyses/{analysisId}/export/pdf', 'JobSeeker\\OfferEvaluationController@exportPdf');

// —— AI Application Assistant (Sprint 3.2) — job-scoped ——
$router->get('/jobseeker/jobs/{job}/application-assistant', 'JobSeeker\\ApplicationAssistantController@show');
$router->post('/jobseeker/jobs/{job}/application-assistant/analyze', 'JobSeeker\\ApplicationAssistantController@analyze');
$router->post('/jobseeker/jobs/{job}/application-assistant/recalculate', 'JobSeeker\\ApplicationAssistantController@recalculate');
$router->get('/jobseeker/jobs/{job}/application-assistant/history', 'JobSeeker\\ApplicationAssistantController@history');
$router->post('/jobseeker/jobs/{job}/application-assistant/history/{history}/delete', 'JobSeeker\\ApplicationAssistantController@deleteHistory');
$router->post('/jobseeker/jobs/{job}/application-assistant/history/{history}/restore', 'JobSeeker\\ApplicationAssistantController@restoreHistory');
$router->post('/jobseeker/jobs/{job}/application-assistant/history/{history}/purge', 'JobSeeker\\ApplicationAssistantController@purgeHistory');
$router->post('/jobseeker/jobs/{job}/application-assistant/history/clear', 'JobSeeker\\ApplicationAssistantController@clearHistory');
$router->get('/jobseeker/jobs/{job}/application-assistant/analyses/{analysisId}/export/pdf', 'JobSeeker\\ApplicationAssistantController@exportPdf');

// —— Job matching (Sprint 2F.3) — resume-scoped ——
$router->get('/jobseeker/resumes/{resume}/recommended-jobs', 'JobSeeker\\JobMatchController@recommended');
$router->get('/jobseeker/resumes/{resume}/jobs/{job}/match', 'JobSeeker\\JobMatchController@show');
$router->post('/jobseeker/resumes/{resume}/jobs/{job}/match/recalculate', 'JobSeeker\\JobMatchController@recalculate');
