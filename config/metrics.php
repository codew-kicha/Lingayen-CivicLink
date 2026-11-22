<?php

/*
 * What each figure on Analytics & Reports measures, and how it is computed (pre-final defense
 * panel: every percentage and metric must say what it measures, with its formula). One place, so
 * the page's info icons, its "How these figures are counted" list, the PDF and the Excel workbook
 * never explain a number differently. Formulas match App\Services\ReportData.
 *
 * key => [name, formula, note]
 */
return [
    'accredited_now' => [
        'Accredited now',
        'Organizations with an active accreditation today',
        'Not limited to the period chosen. An accreditation is active from approval until its expiry date.',
    ],
    'active_organizations' => [
        'Organizations with verified activity',
        'Organizations credited with at least 1 verified activity dated in the period',
        'An organization is credited for activities it led and for those it was tagged on as a partner.',
    ],
    'participation_rate' => [
        'Participation rate',
        'Accredited organizations with at least 1 verified activity ÷ accredited organizations × 100',
        'How many of today\'s accredited organizations did verified community work in the period.',
    ],
    'verified_activities' => [
        'Verified activities',
        'Activities the Desk Office marked Verified whose activity date falls in the period',
        'Activities still waiting to be verified, and rejected ones, are left out of every figure on the page.',
    ],
    'monthly_average' => [
        'Monthly average',
        'Verified activities ÷ number of months in the period',
        null,
    ],
    'residents_reached' => [
        'Residents reached',
        'Sum of the participant counts reported on verified activities in the period',
        'Attendance, not distinct people: a resident who joins three activities is counted three times.',
    ],
    'self_share' => [
        'Self-initiated share',
        'Self-initiated activities ÷ (self-initiated + LGU-organized activities) × 100',
        'Self-initiated: activities an organization logged itself and the office verified. LGU-organized: LGU events the office recorded it attending. Older activities with no organizer recorded are left out. Shown once there are 3 or more; a low share means the organization mostly attends LGU events.',
    ],
    'accreditation_rate' => [
        'Accreditation rate',
        'Applications approved ÷ applications decided (approved + rejected) in the period × 100',
        'Counts the office\'s final decisions dated in the period. Applications still in review are not counted.',
    ],
    'applications_filed' => [
        'Applications filed',
        'Applications submitted to the Desk Office in the month',
        'Drafts not yet submitted are not counted.',
    ],
    'applications_approved' => [
        'Applications approved',
        'Applications approved (accreditation issued) in the month',
        'An application approved in one month may have been filed in an earlier one.',
    ],
    'change' => [
        'Compared with a year earlier',
        'This period\'s figure − the same figure for the same dates one year earlier',
        'A percentage is added when the earlier figure is 10 or more; on a smaller base it would mislead. Shares change in percentage points (pts).',
    ],
    'barangay_activities' => [
        'Activities by barangay',
        'Verified activities in the period led by organizations registered in the barangay',
        'Counted where the leading organization is based, not where the activity took place. Partner credits are not added here.',
    ],
    'per_organization' => [
        'Activities per organization',
        'Verified activities of the sector\'s accredited organizations ÷ number of those organizations',
        null,
    ],
    'performance_score' => [
        'Performance score',
        '25% activity frequency + 25% community reach + 25% compliance timeliness + 25% document currency, shown out of 100',
        'Full marks: 12 verified activities and 1,000 residents reached a year, renewals filed on time, documents current. Informational only; it never affects renewal.',
    ],
    'average_score' => [
        'Average score',
        'Mean of the latest performance score of the sector\'s accredited organizations, out of 100',
        'Informational only; it never affects renewal.',
    ],
    'at_risk' => [
        'At-risk organization',
        'Accredited 6 months or more, and: no or only 1 verified activity in the period, or nothing verified in 6 months, or a self-initiated share under 25%',
        'Newer accreditations are left out: an empty record in the first months is not a warning sign.',
    ],
    'top_contributors' => [
        'Top contributors',
        'Organizations ranked by verified activities in the period (including partner credit), then by residents reached',
        null,
    ],
];
