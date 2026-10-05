<?php

// Accreditation requirements as confirmed by the Civil Society Desk Office (PRD §19).
// DOLE/SEC certification is submitted only if the organization has one.
//
// 'keywords' drive the OCR pre-check (App\Services\DocumentPrecheck): a document "matches" when at
// least 'min_matches' of them appear in its text. Matching ignores case, spacing and punctuation,
// so "By-Laws", "bylaws" and "BY LAWS" are the same word. The check is advisory only.
return [
    'accreditation_form' => [
        'label' => 'Accomplished Accreditation Form',
        'optional' => false,
        'keywords' => ['accreditation', 'application', 'organization', 'name', 'signature', 'barangay'],
        'min_matches' => 2,
    ],
    'officers_members_list' => [
        'label' => 'Updated List of Officers and Members',
        'optional' => false,
        'keywords' => ['officers', 'members', 'president', 'secretary', 'treasurer', 'auditor', 'position'],
        'min_matches' => 2,
    ],
    'constitution_bylaws' => [
        'label' => 'Constitution and By-Laws',
        'optional' => false,
        'keywords' => ['constitution', 'bylaws', 'article', 'section', 'membership', 'amendment'],
        'min_matches' => 2,
    ],
    'fee_receipt' => [
        'label' => 'Accreditation Fee Receipt',
        'optional' => false,
        'keywords' => ['receipt', 'treasur', 'amount', 'payment', 'paid', 'pesos', 'php'],
        'min_matches' => 2,
    ],
    'dole_sec_certification' => [
        'label' => 'DOLE or SEC Certification',
        'optional' => true,
        'keywords' => ['certif', 'registration', 'registered', 'securities', 'exchange', 'commission', 'labor', 'employment', 'dole'],
        'min_matches' => 2,
    ],
];
