<?php

// Accreditation requirements, as listed on the Sangguniang Bayan's CY 2026 forms (resources/forms).
// DOLE/SEC certification is submitted only if the organization has one. 'new_only' documents are
// not asked for on renewals (the renewal form leaves out the constitution and by-laws).
//
// 'checks' drive the document pre-check (App\Services\DocumentPrecheck, resources/js/ocr.js). Each
// check is something the right document always shows, written as what a person would look for, with
// the words that count as finding it. A file "looks right" only when every check is found, so a
// receipt in the by-laws slot is caught. Matching ignores case, spacing and punctuation ("By-Laws",
// "bylaws" and "BY LAWS" are the same word). The result is advice for the reviewer, never a block.
//
// 'expires' => false: the document carries no validity date, so no expiry is asked for or kept
// (an official receipt; the DOLE/SEC certificate of registration).
//
// 'made_online' documents are not uploaded by an applicant: the accreditation form is filled in on
// the website and the officer list comes from the member list, and the system produces both when the
// application is submitted (App\Services\ApplicationSteps). The office's paper filing (assisted
// encoding) still uploads scans of them.
return [
    'accreditation_form' => [
        'label' => 'Accomplished Accreditation Form',
        'optional' => false,
        'made_online' => true,
        'checks' => [
            'the title "Accreditation Form"' => ['accreditation form'],
            'the form\'s fields (name of organization, representative, place of operation)' => ['name of organization', 'name of representative', 'place of operation'],
        ],
    ],
    'officers_members_list' => [
        'label' => 'Updated List of Officers and Members',
        'optional' => false,
        'made_online' => true,
        'checks' => [
            'the word "officers"' => ['officer'],
            'the word "members"' => ['member'],
            'officer positions (president, secretary, treasurer)' => ['president', 'secretary', 'treasurer'],
        ],
    ],
    'constitution_bylaws' => [
        'label' => 'Constitution and By-Laws',
        'optional' => false,
        'new_only' => true,
        'checks' => [
            'the title "Constitution" or "By-Laws"' => ['constitution', 'bylaws'],
            'numbered articles or sections' => ['article', 'section'],
        ],
    ],
    'fee_receipt' => [
        'label' => 'Official Receipt of the Accreditation Fee',
        'optional' => false,
        'expires' => false,
        'checks' => [
            'the word "receipt"' => ['receipt'],
            'the Treasury or an amount paid' => ['treasur', 'amount', 'paid', 'pesos', 'php'],
        ],
    ],
    'dole_sec_certification' => [
        'label' => 'DOLE or SEC Certification',
        'optional' => true,
        'expires' => false,
        'checks' => [
            'a certificate or certification' => ['certif'],
            'DOLE or SEC as the issuing office' => ['labor and employment', 'dole', 'securities and exchange'],
        ],
    ],
];
