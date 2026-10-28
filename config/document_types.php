<?php

// Accreditation requirements, as listed on the Sangguniang Bayan's CY 2026 forms (resources/forms).
// DOLE/SEC certification is submitted only if the organization has one. 'new_only' documents are
// not asked for on renewals (the renewal form leaves out the constitution and by-laws).
//
// 'keywords' drive the OCR pre-check (App\Services\DocumentPrecheck): a document "matches" when at
// least 'min_matches' of them appear in its text. Matching ignores case, spacing and punctuation,
// so "By-Laws", "bylaws" and "BY LAWS" are the same word. The check is advisory only.
//
// 'official_form' links the Sangguniang Bayan's own form (resources/forms) for the chosen
// application type; 'formats' are downloadable formats the system generates
// (RequirementFormatController). Receipts and certificates are issued by other offices, so none.
return [
    'accreditation_form' => [
        'label' => 'Accomplished Accreditation Form',
        'optional' => false,
        'official_form' => true,
        'keywords' => ['accreditation', 'application', 'organization', 'name', 'signature', 'barangay'],
        'min_matches' => 2,
    ],
    'officers_members_list' => [
        'label' => 'Updated List of Officers and Members',
        'optional' => false,
        'formats' => ['pdf', 'xlsx'],
        'keywords' => ['officers', 'members', 'president', 'secretary', 'treasurer', 'auditor', 'position'],
        'min_matches' => 2,
    ],
    'constitution_bylaws' => [
        'label' => 'Constitution and By-Laws',
        'optional' => false,
        'new_only' => true,
        'formats' => ['pdf'],
        'keywords' => ['constitution', 'bylaws', 'article', 'section', 'membership', 'amendment'],
        'min_matches' => 2,
    ],
    'fee_receipt' => [
        'label' => 'Official Receipt of the Accreditation Fee',
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
