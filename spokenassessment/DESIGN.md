# Spoken assessment

Target: Chisimba for KengaLearn (not Learn-the-Birds).

Owner: Derek Keats. Created 16 September 2026 from Assessment Ideas EcoTraining.

## Educational contract

Observe → speak → transcribe → check transcript → feedback → reflect → try again.
This first implementation provides formative feedback, not autonomous final grading.
It evaluates the content of an explanation; a transcript cannot assess pronunciation,
accent, pacing or presentation skill. No automatic gradebook marks are published.
Instructors use existing rubrics and learning outcomes; assessment can proceed with
human feedback if AI is disabled. Student transcript corrections are retained alongside
the original provider transcript. Attempts and the prompt/rubric used are snapshots.

## Ownership

`spokenassessment` owns course activities, attempt history, authorised audio delivery,
transcript approval, formative feedback and the bounded background worker. `rubric`
owns rubric definitions; `ai` owns provider calls, configuration and metadata audits.
A reusable `transcriptionservice` in AI accepts validated local audio; the domain
module authorises access before invoking it. It never accepts a public URL.
Private audio uses File Manager's configured secure root, in a module-owned namespace,
matching the Assignment submission pattern. No public File Manager record is created.
The shared file-delivery range planner supports playback after every access check.

## Access and lifecycle

Logged-in active course members may attempt published activities. Course instructors
and site administrators create activities and review course attempts. A student can
read only their own attempts and cannot edit another person's transcript or feedback.
Every read/write binds activity and attempt to the active context. Public course
visibility does not expose submissions. All mutation endpoints require CSRF.

Attempt states: queued_transcription → transcribing → transcript_ready →
queued_feedback → feedback_processing → feedback_ready. Failures retain the audio
and allow an explicit retry. Provider output and uploaded/transcribed speech are
untrusted data. No tools are given to the feedback model. Feedback is rendered as
escaped text and is marked as AI-generated, revisable by the instructor.

A bounded worker claims work atomically. Running jobs are not silently reissued after
crashes (avoid duplicate provider charges); recovery must be explicit after the lease
expires. Transcript approval freezes the text used for that feedback request. A new
spoken answer creates a new attempt instead of overwriting evidence of learning.

## Limits and operation

Audio-only MP3/M4A/WAV/OGG/WebM/FLAC, maximum 20 MB and 180 seconds, validated on
server with ffprobe as well as byte/MIME checks. Browser recording needs HTTPS and
microphone permission, with upload fallback. Idempotent upload identifiers prevent retry duplicates. Maximum five attempts per activity per
student initially; activity editing does not rewrite existing attempt snapshots.
Module automatic processing and AI transcription are disabled by default until configured. No student audio is sent in
unit/browser fixtures. Real-provider verification uses only an explicitly supplied
or synthetic test recording. Worker logs contain IDs/status only, never transcripts,
recordings, provider response bodies or keys.

## Completion checks

Author/student/other-student/outsider/anonymous access matrix; cross-course ID denial;
CSRF, upload spoofing and duration/size rejection; private audio range reads; transcript
review and original retention; rubric snapshot; feedback schema/prompt-injection bounds;
retry/claim concurrency; save/reload and attempts; no gradebook or audience side effects;
keyboard recording controls, mobile layout, Help/Escape; provider-off recovery.

Future scope: assessed presentation/audio features, deliberate instructor-approved
summative/gradebook integration, offline synchronisation, analytics and retention UI.
These are not prerequisites for the formative workflow and are not represented as
implemented in this release.
