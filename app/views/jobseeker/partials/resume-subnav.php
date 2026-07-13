<?php

declare(strict_types=1);

/**
 * Resume builder section navigation.
 *
 * @var array{id: int|string, title?: string}|object|null $resume
 * @var string $resumeSection
 */

$resumeId = is_array($resume) ? (int) ($resume['id'] ?? 0) : (int) ($resume->id ?? 0);
$section = $resumeSection ?? 'overview';
$base = '/jobseeker/resumes/' . $resumeId;

$links = [
    'overview' => ['label' => 'Overview', 'path' => $base],
    'personal' => ['label' => 'Personal', 'path' => $base . '/personal'],
    'professional' => ['label' => 'Professional', 'path' => $base . '/professional'],
    'education' => ['label' => 'Education', 'path' => $base . '/education'],
    'experience' => ['label' => 'Experience', 'path' => $base . '/experience'],
    'skills' => ['label' => 'Skills', 'path' => $base . '/skills'],
    'languages' => ['label' => 'Languages', 'path' => $base . '/languages'],
    'certifications' => ['label' => 'Certifications', 'path' => $base . '/certifications'],
    'projects' => ['label' => 'Projects', 'path' => $base . '/projects'],
    'achievements' => ['label' => 'Achievements', 'path' => $base . '/achievements'],
    'publications' => ['label' => 'Publications', 'path' => $base . '/publications'],
    'portfolio' => ['label' => 'Portfolio', 'path' => $base . '/portfolio'],
    'references' => ['label' => 'References', 'path' => $base . '/references'],
    'intelligence' => ['label' => 'Intelligence', 'path' => $base . '/intelligence'],
    'career-coach' => ['label' => 'Career Coach', 'path' => $base . '/career-coach'],
    'ai-builder' => ['label' => 'AI Builder', 'path' => $base . '/ai-builder'],
    'cover-letters' => ['label' => 'Cover Letter', 'path' => $base . '/cover-letters'],
    'salary-intelligence' => ['label' => 'Salary', 'path' => $base . '/salary-intelligence'],
    'skill-gap' => ['label' => 'Skill Gap', 'path' => $base . '/skill-gap'],
    'learning-path' => ['label' => 'Learning Path', 'path' => $base . '/learning-path'],
    'portfolio-builder' => ['label' => 'Portfolio AI', 'path' => $base . '/portfolio-builder'],
    'mock-interview' => ['label' => 'Mock Interview', 'path' => $base . '/mock-interview'],
    'job-search-copilot' => ['label' => 'Search Copilot', 'path' => $base . '/job-search-copilot'],
    'offer-evaluation' => ['label' => 'Offer Eval', 'path' => $base . '/offer-evaluation'],
    'recommended-jobs' => ['label' => 'Job matches', 'path' => $base . '/recommended-jobs'],
    'edit' => ['label' => 'Settings', 'path' => $base . '/edit'],
];
?>
<nav class="resume-subnav" aria-label="Resume sections">
    <?php foreach ($links as $key => $link): ?>
        <a class="resume-subnav__link <?= $section === $key ? 'is-active' : '' ?>"
           href="<?= e(app_url($link['path'])) ?>"><?= e($link['label']) ?></a>
    <?php endforeach; ?>
    <a class="resume-subnav__link" href="<?= e(app_url('/jobseeker/resumes')) ?>">All resumes</a>
</nav>
