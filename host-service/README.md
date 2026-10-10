# Host directory

Shared public profiles for events and other consumers. `hostservice` owns names,
biographies and portrait URLs; consumers retain an opaque reference. The directory
also reads published speakers through Webinar's `webinarstore` when installed,
without copying or changing those records. Webinar remains the owner of those
speakers. This local installation does not include Webinar, so that integration
has not been exercised against a live webinar database.

Create profiles through Events → New host. Creation requires an administrator or
a canonical manage permission for Host directory, Events or Webinar. The service
checks permissions independently of the caller. Events checks POST and CSRF,
preserves the draft and selects the new profile. Repeat submissions use stable
creation keys. Profiles are explicitly public; never put private contacts or
arrival instructions into their biographies. Profile editing is not yet exposed.

Install through Module Catalogue before Events. The guarded hook ensures stable
profile identity and registers the `host-service/manage` capability. No schema is
created during normal requests. Author: Derek Keats <derek@dkeats.com>.
