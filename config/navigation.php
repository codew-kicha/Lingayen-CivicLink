<?php

/*
 * Sidebar navigation per role, rendered by the one shared app layout (components/layouts/app).
 * Each item: route to link, `active` route patterns that mark it current, Phosphor icon, and an
 * optional `count` key filled in by the view composer in AppServiceProvider.
 */
return [
    'admin' => [
        'Overview' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'squares-four'],
            ['route' => 'admin.analytics', 'active' => ['admin.analytics', 'admin.analytics.*'], 'label' => 'Analytics & Reports', 'icon' => 'chart-bar'],
        ],
        'Review' => [
            ['route' => 'admin.applications.index', 'active' => ['admin.applications.*'], 'label' => 'Applications', 'icon' => 'file-text', 'count' => 'pendingApplications'],
            ['route' => 'admin.activities.index', 'active' => ['admin.activities.*'], 'label' => 'Verify activities', 'icon' => 'check-square', 'count' => 'pendingActivities'],
        ],
        'Directory' => [
            ['route' => 'admin.organizations.index', 'active' => ['admin.organizations.*'], 'label' => 'Organizations', 'icon' => 'buildings'],
            ['route' => 'admin.scorecards.index', 'active' => ['admin.scorecards.*'], 'label' => 'Scorecards', 'icon' => 'gauge'],
        ],
        'Content' => [
            ['route' => 'admin.news.index', 'active' => ['admin.news.*'], 'label' => 'News posts', 'icon' => 'newspaper'],
            ['route' => 'admin.annual-reports.index', 'active' => ['admin.annual-reports.*'], 'label' => 'Annual reports', 'icon' => 'file-pdf'],
        ],
        'System' => [
            ['route' => 'admin.accounts.index', 'active' => ['admin.accounts.*'], 'label' => 'Accounts', 'icon' => 'users'],
            ['route' => 'admin.audit.index', 'active' => ['admin.audit.*'], 'label' => 'Audit log', 'icon' => 'list-magnifying-glass'],
            ['route' => 'admin.system-check', 'label' => 'System check', 'icon' => 'shield-check'],
        ],
    ],

    'cso_rep' => [
        'Overview' => [
            ['route' => 'cso.dashboard', 'label' => 'Dashboard', 'icon' => 'squares-four'],
        ],
        'My organization' => [
            ['route' => 'cso.profile.edit', 'active' => ['cso.profile.*'], 'label' => 'Organization profile', 'icon' => 'identification-card'],
            ['route' => 'cso.activities.index', 'active' => ['cso.activities.*'], 'label' => 'Activities', 'icon' => 'handshake'],
        ],
        'Accreditation' => [
            ['route' => 'cso.applications.index', 'active' => ['cso.applications.*'], 'label' => 'Applications', 'icon' => 'file-text'],
        ],
    ],
];
