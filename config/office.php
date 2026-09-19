<?php

// Static public-page content for Milestone 1. Realistic placeholders, to be replaced with the
// client's own copy (PRD §14, Day 6).
return [

    'officials' => [
        ['name' => 'To be supplied by PESO', 'position' => 'PESO Manager'],
        ['name' => 'To be supplied by PESO', 'position' => 'Accreditation Officer'],
        ['name' => 'To be supplied by PESO', 'position' => 'Records Officer'],
        ['name' => 'To be supplied by SB Secretariat', 'position' => 'Sangguniang Bayan Secretary'],
    ],

    'process' => [
        [
            'title' => 'File the application',
            'detail' => 'Register your organization and submit the form online, or bring a filled-in paper form to the PESO office.',
        ],
        [
            'title' => 'Document check',
            'detail' => 'PESO reviews each uploaded requirement for completeness and validity.',
        ],
        [
            'title' => 'PESO review',
            'detail' => 'The office evaluates the application against the DILG MC 2022-083 criteria.',
        ],
        [
            'title' => 'Sangguniang Bayan readings',
            'detail' => 'Endorsed applications move through first, second, and third reading at the SB Secretariat.',
        ],
        [
            'title' => 'Accreditation issued',
            'detail' => 'Approved organizations are added to the public directory and may sit on Local Special Bodies.',
        ],
    ],

    'requirement_notes' => [
        'sec_registration' => 'From SEC, DTI, or CDA, whichever registered your organization.',
        'board_resolution' => 'Authorizing the application and naming your representative.',
        'list_of_officers' => 'Current officers and members, with positions.',
        'financial_statement' => 'Covering the most recently completed fiscal year.',
        'work_program' => 'Planned activities and advocacy for the coming year.',
        'barangay_clearance' => 'From the barangay where your organization principally operates.',
    ],

    'faqs' => [
        [
            'question' => 'How long does accreditation last?',
            'answer' => 'Accreditation runs for three years, after which organizations file a renewal application.',
        ],
        [
            'question' => 'What does the performance score affect?',
            'answer' => 'Nothing. The score is informational only and does not affect renewal decisions. It exists so organizations and PESO can see contribution over time.',
        ],
        [
            'question' => 'Can PESO staff file on our behalf?',
            'answer' => 'Yes. Bring your printed requirements to the office and staff will encode the application into the same system, which you can then track online.',
        ],
        [
            'question' => 'Are our members\' names published?',
            'answer' => 'No. The public directory shows organization-level information only. Member names and contact details are visible to PESO administrators only.',
        ],
        [
            'question' => 'What happens if a document expires?',
            'answer' => 'The system tracks expiry dates and notifies your representative ahead of time so the document can be replaced before it affects your standing.',
        ],
    ],

];
