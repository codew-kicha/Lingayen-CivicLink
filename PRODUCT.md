# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Lingayen CivicLink's public site serves three audiences equally (confirmed 17 Nov 2026), each needing its own clear path:

- **Residents and the public** checking which civil society organizations (CSOs) are accredited by the Municipality of Lingayen, Pangasinan, and what verified community work they have done.
- **CSO representatives** learning the accreditation process and starting or renewing an application online.
- **LGU officials and partners** (Sangguniang Bayan members, the Local Development Council and other Local Special Bodies) looking up accredited CSOs for seats and partnerships.

Signed-in CSO representatives and the PESO administrator use separate app areas (CSO dashboard, admin), which are not part of the public site.

## Product Purpose

CivicLink moves CSO accreditation in Lingayen off paper and keeps a continuing public record of what accredited organizations do between accreditation cycles. It is run by the Civil Society Desk Office of the Public Employment Service Office (PESO). Success on the public site: a resident can confirm an organization's accreditation and see its verified activity in seconds; a CSO understands the steps and starts an application; an official can find accredited CSOs by sector or barangay.

## Positioning

The official registry of the Municipality of Lingayen, not a directory anyone can join: every organization listed holds an active accreditation issued after the Desk Office's validation and the Sangguniang Bayan committee's hearing, and every activity shown was verified by the Desk Office against a submitted sign-in sheet. The figures on the site are counted live from that verified record.

## Operating Context

- Accreditation follows RA 7160 (Local Government Code of 1991) and DILG Memorandum Circular No. 2025-060.
- Flow: register and fill in the Accreditation Form online, the Desk Office approves the form, the CSO uploads the remaining requirements (official receipt only when the fee applies, constitution and by-laws, DOLE/SEC certification), the Desk Office validates them and forwards the application to the Sangguniang Bayan committee, which holds one hearing (Favorable, Deferred or Returned, with remarks), and after a favorable hearing the Desk Office approves it and the accreditation is issued: three years when filed within a free period (opened by a DILG memorandum after each new administration, when no fee is charged), one year otherwise (PHP 1,000 new, PHP 500 renewal, renewed yearly). Four phases: Application, Validation, Committee hearing, Approval.
- Accredited CSOs log community activities with a sign-in sheet; the office verifies them; verified activities appear on the organization's public page.
- 32 barangays; CSO sectors as listed in config/sectors.php.
- Office: Civil Society Desk Office, 2nd Floor, PESO Office, Municipal Hall, Lingayen, Pangasinan; Monday to Thursday, 7:00 AM to 6:00 PM.
- Many residents and CSO representatives use phones.

## Capabilities and Constraints

- Laravel 12 with Blade components, Alpine.js and Tailwind 3.4; design tokens in resources/css/tokens.css (a test forbids raw colours and off-scale spacing in views).
- Hosted on Hostinger shared hosting: no Node at runtime, no queue worker; assets are built with Vite before upload.
- Public pages: Home, About, Accreditation, Accredited CSOs (directory with search, barangay and sector filters), each organization's page, Resources (news posts, annual reports), Contact (form), Privacy.
- Public pages show organization-level information only; member names and contact details are never public.
- English throughout.

## Brand Commitments

- Keep the existing colour palette (maroon and amber tokens), the hero video section, and the nav bar (identity bar and maroon menu bar) as they are. Confirmed by the user, 17 Nov 2026.
- The Lingayen municipal seal (public/images/brand/lingayen-seal.png) is the identity mark; the hero video (public/videos/lingayen_hero.mp4) shows the seal over accredited CSOs' logos.
- Voice: official LGU. Formal and precise, citing the law and memorandum circulars where relevant.
- Names: "Lingayen CivicLink" (the system) and "CSO Desk Office" / "Civil Society Desk Office" (the office).

## Evidence on Hand

- Real photos: public/images/events/cso1.jpg to cso7.jpg (CSO events in Lingayen). No more photos are coming.
- public/images/stock/ holds five Unsplash placeholder photos, not of Lingayen (see SOURCES.txt); not to be presented as real.
- The seal; the hero video and its poster.
- CSO logos only where an organization uploaded one to its profile (logo_path); most have none.
- Live data: accredited organizations, sectors, barangays, verified activities with dates and residents reached, news posts, annual reports (PDF), office details, office officials (About page), legal basis.
- Absent and not to be invented: testimonials, quotes from CSOs or residents, ratings, statistics beyond the live record.

## Product Principles

- A public record, not a brochure: what is shown is what was verified, counted live.
- Three audiences, three clear paths; none has to wade through another's content.
- Official precision over promotion: say what the law and the office do, plainly.
- Privacy by default: organizations are public, their members are not.

## Accessibility & Inclusion

WCAG 2.1 AA as the working standard; usable on low-end phones and slow connections; respects reduced-motion and data-saver preferences (already done for the hero video).
