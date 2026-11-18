# Questions to confirm with the Civil Society Desk Office

Raised by the panel at the pre-final defense (November 2026). Each one changes how the system works,
so nothing is built until the office answers. For each: what the panel said, what the client said so
far, and what would change.

## 1. When the accreditation fee is paid

- **Panel:** asking for the Official Receipt with the application is "too early". An organization
  pays PHP 1,000 before anyone has checked whether it qualifies, and a rejected applicant has paid
  for nothing.
- **Client so far:** the process is strict because the Desk Office follows the LGU's procedure; the
  receipt is one of the five requirements on the Sangguniang Bayan's form.
- **Built (14 November 2026), following the panel:** the application form and member list are
  submitted on their own. The Desk Office approves the form first; only then is the CSO notified
  and allowed to upload the receipt, the constitution and the DOLE/SEC certificate (step 6). The
  committee hearing comes once those are validated. The server refuses requirement uploads before
  approval. Flow: Application → Validation (form, then requirements) → Committee hearing →
  Approval.
- **Still to confirm with the office:** that this order matches the LGU's procedure. If the office
  needs the receipt at filing after all, it can approve the form on the same day it is filed.
- **Client's answer on who pays (18 November 2026):** the fee applies only to organizations that
  miss the free period. After each new LGU administration, a DILG memorandum gives CSOs about three
  months to apply or renew for free, and that accreditation lasts three years. Organizations that
  miss it pay (PHP 1,000 new, PHP 500 renewal) and renew yearly, paying the renewal fee each time.
- **Built:** the Desk Office records each free period (memorandum, first and last free day) on the
  Free filing periods page. An application submitted inside one never asks for the receipt and its
  accreditation defaults to three years; outside, the receipt is required and it defaults to one
  year (the date can still be changed at approval). The office can also waive or require the fee on
  one application until it goes to the committee; requiring it after the requirements are in
  reopens them, and the organization is told either way. The public pages say whether filing is
  free right now.
- **Still to confirm:** the exact dates of the period under DILG MC No. 2025-060 (the demo data uses
  1 July to 30 September 2025), and whether the three years run from approval or to the end of
  the administration's term.

## 2. Sangguniang Bayan readings (resolved, 18 November 2026)

- **Panel:** expected the CSO to receive a resolution (or at least a reference) at the first, second
  and third readings, not only a stage label.
- **Client's answer:** there are no first to third readings for CSO accreditation. The process is
  four phases: Application, Validation, Committee hearing, Approval. The Sangguniang Bayan
  committee holds one hearing; the Desk Office approves directly after a favorable one. No
  resolution number is recorded.
- **Built:** the reading stages were replaced. After validating the requirements, the Desk Officer
  forwards the application to the committee, then records the hearing's result (date, committee,
  Favorable, Deferred or Returned, and remarks). The CSO is told by notification and email, sees
  the four phases and every hearing's remarks on its application page, and Approve opens only
  after a Favorable hearing. A record made by mistake can be undone without notifying again.

## 3. Signatures on the Accreditation Form

- **Question:** the Accreditation Form is now filled in on the website and the system produces the
  completed form. The representative ticks a certification and types their name. Does the office
  accept that, or must the printed form still carry a handwritten signature?
- **If a wet signature is required:** after submitting, the CSO prints the generated form, signs it
  and uploads the signed scan (or brings it to the office); approval waits for it.
- **Today:** the online certification (name, date and time) is printed on the generated form.

## 4. Reading sign-in sheets with an outside AI service

- **Question:** when the AI reader is switched on, photos of sign-in sheets (residents' names and
  signatures) and the activity photos (which may show residents) are sent to Anthropic's API to
  count names, read the date and venue, and say whether the photos show the activity. Under the Data
  Privacy Act (RA 10173) the office, as the personal information controller, should approve this
  and the privacy notice must say so. The AI is told not to name or describe individual people.
- **If approved:** set `ANTHROPIC_API_KEY` and `AI_SHEET_READER=true` on the server. Cost is about
  PHP 20 to 40 per 1,000 sheets with Claude Haiku 5.5.
- **If not:** leave it off. The free checks still run on the server and nothing leaves it: reused
  or re-saved pictures (perceptual fingerprint), the photos' camera data (date taken, editing app,
  GPS), the typed sheet against the date, title and venue, and late logging. Together they give the
  0-100 confidence score and the Verified / Needs review / Flagged result.
