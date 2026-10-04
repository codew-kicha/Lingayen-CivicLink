<?php

// Public-page content. Officials, address, and hours confirmed by the client (PRD §11).
// Phone and email are still pending from the client, so they are deliberately absent.
return [

    'office' => [
        'name' => 'Civil Society Desk Office',
        'parent' => 'Public Employment Service Office, Municipality of Lingayen',
        'address' => '#1 Bengson Street, Lingayen, Pangasinan 2401',
        // Unconfirmed whether the office is closed Friday to Sunday or keeps other hours (PRD §11).
        'hours' => 'Monday to Thursday, 7:00 AM to 6:00 PM',
        'facebook' => 'https://www.facebook.com/pesolingayen/',
    ],

    'officials' => [
        ['name' => 'Hon. Josefina “Iday” V. Castañeda', 'position' => 'Municipal Mayor'],
        ['name' => 'Hon. Jay Mark Kevin D. Crisostomo', 'position' => 'Municipal Vice Mayor'],
        ['name' => 'Van Macley Moulic', 'position' => 'Civil Society Desk Officer'],
    ],

    // Step 4 assumes three SB readings; the client has not yet confirmed the count (PRD §19).
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
        'accreditation_form' => 'The office\'s accreditation form, filled in and signed by your president.',
        'officers_members_list' => 'Current officers and members, with positions.',
        'constitution_bylaws' => 'Your organization\'s adopted constitution and by-laws.',
        'fee_receipt' => 'PHP 1,000 for new applications, paid at the Municipal Treasury. PHP 500 for renewals, which is sometimes waived.',
        'dole_sec_certification' => 'Submit only if your organization is registered with DOLE or SEC.',
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
