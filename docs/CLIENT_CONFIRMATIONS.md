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
  Sangguniang Bayan readings start once those are in. The server refuses requirement uploads before
  approval. Flow: Submitted → Requirements pending → Requirements submitted → Under review →
  Accredited.
- **Still to confirm with the office:** that this order matches the LGU's procedure. If the office
  needs the receipt at filing after all, it can approve the form on the same day it is filed.

## 2. A resolution for each Sangguniang Bayan reading

- **Panel:** expects the CSO to receive a resolution (or at least a reference) at the first, second
  and third readings, not only a stage label.
- **Client so far:** the Sangguniang Bayan does not issue anything per reading; only the final
  endorsement matters.
- **If the office confirms resolutions exist:** each stage the Desk Officer records would take a
  resolution number and date (and optionally the scanned resolution), shown to the CSO on its
  application page and in the status email.
- **Today:** the Desk Officer records the stage only (first, second, third reading, endorsed).

## 3. Signatures on the Accreditation Form

- **Question:** the Accreditation Form is now filled in on the website and the system produces the
  completed form. The representative ticks a certification and types their name. Does the office
  accept that, or must the printed form still carry a handwritten signature?
- **If a wet signature is required:** after submitting, the CSO prints the generated form, signs it
  and uploads the signed scan (or brings it to the office); approval waits for it.
- **Today:** the online certification (name, date and time) is printed on the generated form.

## 4. Reading sign-in sheets with an outside AI service

- **Question:** when the AI reader is switched on, photos of sign-in sheets (residents' names and
  signatures) are sent to Anthropic's API to count names and read the date. Under the Data Privacy
  Act (RA 10173) the office, as the personal information controller, should approve this and the
  privacy notice must say so.
- **If approved:** set `ANTHROPIC_API_KEY` and `AI_SHEET_READER=true` on the server. Cost is about
  PHP 20 to 40 per 1,000 sheets with Claude Haiku 5.5.
- **If not:** leave it off. The free checks (reused photo, date and attendance checks, the typed
  header) still run on the server and nothing leaves it.
