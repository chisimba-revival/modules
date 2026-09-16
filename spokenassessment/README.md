# Spoken assessment — Chisimba for KengaLearn

This module turns the EcoTraining spoken-assessment idea into a course activity:
**observe → speak → check transcript → feedback → reflect → try again**.

It is formative. It never publishes marks, writes the gradebook, changes memberships,
or sends audience emails. Learn-the-Birds is not the target of this work.

## Where to find it

Install Spoken assessment through Module Catalogue, then enter a course and open
Spoken assessment from the assessment tools. Direct local route:
`index.php?module=spokenassessment` (an active course and membership are required).
The page identifies the active course. Its Help button explains both teaching and
learner journeys, including privacy and unavailable automatic processing. The compact
Help drawer includes recording limits, faithful transcript correction, upload recovery
and the difference between retrying processing and making a new attempt. Teaching
guidance appears only for the course teaching team and administrators. Escape closes
the drawer and returns focus to its button.

An instructor creates a prompt and learning outcomes, optionally chooses a rubric
belonging to the course, and publishes the activity. A learner can record in the
browser or upload audio, review it, and submit up to five attempts. Every attempt
retains the activity and rubric snapshot, original automatic transcript, approved
transcript, formative feedback, instructor feedback and learner reflection.

Failed uploads keep the local file and preview available. The learner can download
a copy before leaving. An upload identifier prevents a lost HTTP response from
creating another attempt when retried. No offline/background upload is claimed.

## Installation and operation

Deploy the corresponding **modules and framework** feature changes together, after
normal staging checks. The framework changes add reusable AI transcription/audio
validation, a responsive native-audio skin rule, and correct handling of explicit
unique constraints in the shared table-creation helper. The modules change includes a
small canonical Rubric service fix for lower-case database column names.

Requirements:

- Current Chisimba course/identity/file-permission services, AI, Rubric, Help,
  File Manager and Time and Date services.
- PHP 8 with cURL, fileinfo, mbstring and process execution available.
- `/usr/bin/ffprobe` (Debian/Ubuntu: provided by `ffmpeg`). Keep this dependency in
  the deployment's image/build definition, not only a running container.
- File Manager's `SECUREFODLER` setting (the existing spelling) pointing to a
  writable private directory outside the web document root. The module creates
  `spokenassessment` there with mode 0700; recordings use mode 0600.
- HTTPS for browser microphone access; uploads remain available without recording.
- PHP upload limit at least 20M, POST limit above 20M (for example 24M), and a
  matching web-server request limit. Module validation always enforces 20 MiB and
  180 seconds. Browser auto-stop leaves a small margin for encoder timing.
- InnoDB tables and utf8mb4. Verify both new tables and their unique ID indexes
  exist after installation; the legacy installer can report success despite a
  underlying schema failure.

Automatic processing is **off by default**. Human listening, faithful manual
transcripts and instructor feedback work while it is off. To enable it deliberately:

1. Configure the canonical AI service and its server-side provider credentials.
2. Set `AI_TRANSCRIPTION_STATE=enabled` and choose `AI_TRANSCRIPTION_MODEL`
   (`gpt-4o-mini-transcribe`, `gpt-4o-transcribe`, or `whisper-1`). This is separate
   from the AI model used to produce feedback.
3. Set this module's `SPOKEN_AI_STATE=enabled` only after the privacy notice and
   provider configuration have been reviewed for the site.
4. Run the bounded CLI worker from the **installed site**, for example:

   `php packages/spokenassessment/scripts/run_worker.php 5`

   The batch limit is 1–10. Run it through the site's supervised scheduling
   mechanism as the application user. No scheduler has been installed by this work.
5. Verify a complete round trip with a synthetic or explicitly authorised test
   recording before enabling this for learners.

Workers atomically claim work, recheck course access and save only if their claim
is still current. They do not silently retry abandoned provider requests. After
15 minutes an authorised viewer can explicitly retry a stalled request; the UI
explains that the original request may already have incurred a provider charge.
Worker output is counts/status only. Private transcripts bypass generic database
query/value debug logging. Shared AI audit rows contain metadata, not speech.

## Verification

Run the independent checks:

- `php spokenassessment/tests/workflow_test.php`
- `php rubric/tests/rubric_context_case_test.php`
- Framework: `php app/core_modules/ai/tests/audio_inspection_test.php`
- Framework: `php app/core_modules/ai/tests/transcription_boundary_test.php`

In a **disposable local installed runtime**, with no queued or running attempts:

`SPOKEN_LOCAL_TEST=1 php packages/spokenassessment/tests/storage_integration.php`

That check creates and removes its own rows, verifies cross-process persistence,
rollback, Unicode and competing worker claims. It refuses to run over an active queue.

`tests/browser.cjs` is the local browser regression. It requires Playwright Core,
Chrome and a private fixture JSON with `context`, `users` (teacher, student, other,
outsider) and `password`. The context must start with `spqa`; it creates an activity
inside that disposable course. Use `SPOKEN_BROWSER_FIXTURE` to select that file and
`PLAYWRIGHT_CORE` to select the installed Playwright package. The URL is deliberately
restricted to the local `chisimba.test:8445` runtime. It uses a fake microphone,
never the user's actual microphone. No credentials belong in source control.

See [TESTING.md](TESTING.md) for the actual results and remaining rollout checks,
and [DESIGN.md](DESIGN.md) for the educational and architectural contract.

## Explicit limits

This does not assess pronunciation, accent, pacing or presentation quality from a
transcript. There is no automated final grade, retention-management UI, offline sync
or scheduled worker yet. The maximum is five attempts per activity and three minutes
per recording. Human feedback is separate from the AI suggestion. Real-provider
quality, latency and cost still need a supervised test before production rollout.
