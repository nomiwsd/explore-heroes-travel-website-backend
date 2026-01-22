<?php

return [
    'dashboard' => [
        'dashboard_access',
    ],
    'tour' => [
        'tour_view',
        'tour_create',
        'tour_update',
        'tour_delete',
        'tour_category_manage', // For Categories page
        'tour_theme_manage',    // For Themes page
        'tour_manage_others',
    ],
    'location' => [
        'location_view',
        'location_create',
        'location_update',
        'location_delete',
        'location_manage_others',
    ],
    'page' => [
        'page_view',
        'page_create',
        'page_update',
        'page_delete',
        'page_manage_others',
    ],
    'news' => [
        'news_view',
        'news_create',
        'news_update',
        'news_delete',
        'news_manage_others',
        'newsletter_manage',
    ],
    'menu' => [
        'menu_view',
        'menu_create',
        'menu_update',
        'menu_delete',
    ],
    'media' => [
        'media_manage', // Upload/Delete/View
    ],
    'review' => [
        'review_view',
        'review_manage', // Reply/Approve/Delete
    ],
    'form' => [
        'form_view',
        'form_delete',
    ],
    'seo' => [
        'seo_view',         // Access SEO section
        'seo_global_manage', // Global Settings
        'seo_sitemap_manage', // Sitemap & Robots
        'seo_redirect_manage', // Redirects
    ],
    'user' => [
        'user_view',
        'user_create',
        'user_update',
        'user_delete',
        'user_manage_others',
    ],
    'role' => [ // Usually managed by Super Admin, but granular control exists
        'role_view',
        'role_create', 
        'role_update',
        'role_delete',
    ],
    'language' => [
        'language_manage', // Languages CRUD
        'translation_manage', // Translations Page
    ],
    'setting' => [
        'setting_view',
        'setting_update',
    ],
    'audit' => [
        'audit_log_view'
    ]
];
