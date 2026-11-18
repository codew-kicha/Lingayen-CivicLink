<?php

// Public-page content. Officials, address, hours, phone and email confirmed by the client (PRD §11).
return [

    'office' => [
        'name' => 'Civil Society Desk Office',
        'parent' => 'Public Employment Service Office, Municipality of Lingayen',
        'address' => '2nd Floor, PESO Office, Municipal Hall, Lingayen, Pangasinan',
        'email' => 'vandeveramoulic@gmail.com',
        'phone' => '0964 057 4151',
        'phone_link' => '+639640574151',   // tel: links (tap to call on phones)
        // Unconfirmed whether the office is closed Friday to Sunday or keeps other hours (PRD §11).
        'hours' => 'Monday to Thursday, 7:00 AM to 6:00 PM',
        'facebook' => 'https://www.facebook.com/pesolingayen/',
    ],

    'officials' => [
        ['name' => 'Hon. Josefina “Iday” V. Castañeda', 'position' => 'Municipal Mayor'],
        ['name' => 'Hon. Jay Mark Kevin D. Crisostomo', 'position' => 'Municipal Vice Mayor'],
        ['name' => 'Cezca Katrina F. Mararac', 'position' => 'PESO Manager'],
        ['name' => 'Van Macley Moulic', 'position' => 'Civil Society Desk Officer'],
    ],

    // Accreditation fees, in pesos, and how long accreditation lasts (client, Nov 2026). Free during
    // the period a DILG memorandum opens after each new LGU administration (FeeFreePeriod, set by the
    // Desk Office), and then valid for three years. Outside it the fee applies and accreditation lasts
    // one year, renewed yearly with the renewal fee.
    'fees' => ['new' => 1000, 'renewal' => 500],
    'validity_years' => ['free' => 3, 'paid' => 1],

    // The accreditation path in the client's four phases (Nov 2026): Application, Validation,
    // Committee hearing, Approval. The panel's order holds inside Validation: the form is approved
    // before the fee and other requirements. `note` marks a step's one fact a visitor must not miss;
    // `outcomes` lists what a step can end in.
    'process' => [
        [
            'title' => 'Application',
            'detail' => 'Create an account, list at least five members, and fill in the Accreditation Form online. Walk-in applicants may file at the PESO office.',
            'who' => 'Your organization\'s representative',
            'points' => [
                'Confirm your email with a 6-digit code',
                'Upload the member list from Excel, or type it in',
                'Your progress is saved; finish later at any step',
            ],
        ],
        [
            'title' => 'Validation',
            'detail' => 'The Desk Office validates the form and the member list against DILG MC No. 2025-060. Once the form is approved, you upload the other requirements (with the official receipt when the fee applies), and the office validates those too.',
            'note' => 'Free during a DILG free filing period; otherwise nothing is paid before the form is approved.',
            'who' => 'Civil Society Desk Office, with your uploads',
            'points' => [
                'No fee within a free filing period; outside it, PHP 1,000 new and PHP 500 renewal',
                'PDF, JPG or PNG up to 5 MB, each checked as you upload it',
                'Anything to fix comes back to you with a note',
            ],
        ],
        [
            'title' => 'Committee hearing',
            'detail' => 'The validated application goes to the Sangguniang Bayan committee for one hearing. You are told its outcome, with the office\'s remarks.',
            'outcomes' => ['Favorable', 'Deferred', 'Returned'],
            'who' => 'Sangguniang Bayan committee',
            'points' => [
                'Favorable: recommended for approval',
                'Deferred: heard again at a later sitting',
                'Returned: comply with the remarks first',
            ],
        ],
        [
            'title' => 'Approval',
            'detail' => 'After a favorable hearing, the Desk Office approves the application and issues the accreditation: three years when filed free, one year (renewed yearly) when the fee applied. The organization joins the public directory and may sit on a Local Special Body.',
            'who' => 'Civil Society Desk Office',
            'points' => [
                'A reference code for your accreditation',
                'Your verified activities appear on your public page',
                'A reminder before it expires, so you can renew in time',
            ],
        ],
    ],

    // What each Sangguniang Bayan form asks for, word for word from the CY 2026 forms (resources/forms).
    'paper_forms' => [
        'new' => [
            'title' => 'New application',
            'note' => 'Submit everything in 2 copies.',
            'items' => [
                'Accomplished Accreditation Form',
                'Updated list of officers and members, including their contact numbers',
                'Constitution and by-laws',
                'Official receipt of the accreditation fee from the Municipal Treasury (PHP 1,000.00); not needed within a free filing period',
                'DOLE or SEC certification (if registered)',
            ],
        ],
        'renewal' => [
            'title' => 'Renewal',
            'note' => null,
            'items' => [
                'Accomplished Accreditation Form',
                'Updated list of officers and members, including their contact numbers',
                'Official receipt of the renewal fee from the Municipal Treasury (PHP 500.00); not needed within a free filing period',
                'DOLE or SEC certification',
            ],
        ],
    ],

    'requirement_notes' => [
        'accreditation_form' => 'The office\'s accreditation form, filled in and signed by your president.',
        'officers_members_list' => 'Current officers and members, with their positions and contact numbers.',
        'constitution_bylaws' => 'Your organization\'s adopted constitution and by-laws. New applications only.',
        'fee_receipt' => 'From the Municipal Treasury: PHP 1,000 for new applications, PHP 500 for renewals. Not needed within a free filing period.',
        'dole_sec_certification' => 'Submit only if your organization is registered with DOLE or SEC.',
    ],

    'faqs' => [
        [
            'question' => 'How long does accreditation last?',
            'answer' => 'Three years when you file within a free filing period. Filed outside it, accreditation lasts one year and is renewed yearly with the PHP 500 renewal fee.',
        ],
        [
            'question' => 'When is accreditation free?',
            'answer' => 'After each new municipal administration takes office, a DILG memorandum gives organizations about three months to apply or renew for free. Applications filed in that period need no official receipt. Outside it, the fee is PHP 1,000 for a new application and PHP 500 for a renewal, paid at the Municipal Treasury after the Desk Office approves your form.',
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
