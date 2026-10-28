{{--
    Privacy notice (Data Privacy Act of 2012, RA 10173). Describes only what this system actually
    collects and does; keep it in step with the code when data handling changes.
--}}
<x-layouts.public title="Privacy notice">
    <x-page-banner title="Privacy notice" photo="events/cso2.jpg" alt="Officers of civil society organizations at a CSO assembly in Lingayen"
                   lede="What the Civil Society Desk Office collects through this website, why, who can see it, and your rights under the Data Privacy Act of 2012." />

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="space-y-12 text-ink">
            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">Who we are</h2>
                <p class="mt-3 leading-relaxed text-muted">
                    This website is run by the {{ config('office.office.name') }} of the Public Employment Service Office,
                    Municipality of Lingayen, to process the accreditation of civil society organizations (CSOs) and to record
                    their community activities, under RA 7160 (Local Government Code of 1991) and DILG Memorandum Circular
                    No. 2025-060.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">What we collect</h2>
                <ul class="mt-3 list-disc space-y-2 pl-6 leading-relaxed text-muted">
                    <li><strong class="text-ink">About the representative:</strong> full name, email address, mobile number and password (stored only in scrambled form).</li>
                    <li><strong class="text-ink">About the organization:</strong> its name, sector, barangay, advocacy, logo, structure, and the names, positions and contact numbers of its officers and members.</li>
                    <li><strong class="text-ink">Accreditation requirements:</strong> the documents you upload, such as the accreditation form, officer list, by-laws and receipt.</li>
                    <li><strong class="text-ink">Activities:</strong> the activities you log, residents reached, partner organizations and sign-in sheets.</li>
                    <li><strong class="text-ink">Security records:</strong> when an account signs in and the internet address it used, kept to protect accounts.</li>
                    <li><strong class="text-ink">Enquiries:</strong> your name, email and message when you use the Contact form.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">Why we use it</h2>
                <ul class="mt-3 list-disc space-y-2 pl-6 leading-relaxed text-muted">
                    <li>To review applications and issue, renew or revoke accreditation.</li>
                    <li>To verify logged activities and compute each organization's performance score.</li>
                    <li>To contact you about your application, your activities and your accreditation's expiry.</li>
                    <li>To list accredited organizations in the public directory and to prepare the office's reports.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">Who can see it</h2>
                <ul class="mt-3 list-disc space-y-2 pl-6 leading-relaxed text-muted">
                    <li><strong class="text-ink">The public</strong> sees only accredited organizations' name, sector, barangay, advocacy, logo and verified activities. Member names and contact details are never shown publicly.</li>
                    <li><strong class="text-ink">The Desk Office</strong> sees everything submitted, to review it, and shares what is needed with the Sangguniang Bayan, which decides on accreditation.</li>
                    <li><strong class="text-ink">Each representative</strong> sees only their own organization's records.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">How we protect it</h2>
                <p class="mt-3 leading-relaxed text-muted">
                    Uploaded documents are kept in private storage and opened only by the organization's representative and
                    the Desk Office. The site uses encrypted connections (HTTPS), new accounts confirm their email with a code,
                    and important actions by office staff are recorded in an audit log. We keep the information while the
                    organization is registered with the office and as government record-keeping rules require.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-bold text-brand-900">Your rights</h2>
                <p class="mt-3 leading-relaxed text-muted">
                    Under the Data Privacy Act you may ask to be informed of how your data is used, to see it, to have it
                    corrected, to object to its use, and to have it removed when it is no longer needed. You can correct most
                    details yourself in your organization profile and account settings. For anything else, contact the office:
                </p>
                <p class="mt-3 leading-relaxed">
                    {{ config('office.office.name') }}, {{ config('office.office.address') }}<br>
                    <span class="tabular-nums">{{ config('office.office.phone') }}</span> · <span class="break-all">{{ config('office.office.email') }}</span>
                </p>
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    You may also bring a complaint to the National Privacy Commission.
                </p>
            </section>
        </div>
    </div>
</x-layouts.public>
