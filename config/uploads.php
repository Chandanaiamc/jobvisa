<?php

declare(strict_types=1);

/**
 * Upload / storage paths for JobVisa.lk (local disk).
 */
return [
    'disk_path' => env('UPLOAD_DISK_PATH', 'storage/uploads'),
    'max_avatar_bytes' => (int) env('UPLOAD_MAX_AVATAR_BYTES', 3_145_728),
    'max_cv_bytes' => (int) env('UPLOAD_MAX_CV_BYTES', 5_242_880),
    'max_certificate_bytes' => (int) env('UPLOAD_MAX_CERTIFICATE_BYTES', 5_242_880),
    'max_project_image_bytes' => (int) env('UPLOAD_MAX_PROJECT_IMAGE_BYTES', 5_242_880),
    'max_project_document_bytes' => (int) env('UPLOAD_MAX_PROJECT_DOCUMENT_BYTES', 5_242_880),
    'max_publication_bytes' => (int) env('UPLOAD_MAX_PUBLICATION_BYTES', 10_485_760),
    'max_portfolio_image_bytes' => (int) env('UPLOAD_MAX_PORTFOLIO_IMAGE_BYTES', 5_242_880),
    'avatar_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'cv_mimes' => ['application/pdf'],
    'certificate_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
    'project_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'project_document_mimes' => ['application/pdf'],
    'publication_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ],
    'portfolio_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
];
