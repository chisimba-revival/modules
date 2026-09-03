# Chisimba Notifications API v1

## Purpose and boundaries

Notifications is the durable record of **who needs to know what** and whether each recipient has read it. Producing modules publish structured events through `notificationservice`. Human-facing clients—including Chisimba's own notification centre—consume the HTTP API. Communications remains responsible for email and future external delivery channels. Discussion remains responsible for discussions, posts, subscriptions and marks.

Real-time delivery is deliberately not a source of truth. Polling, Server-Sent Events, mobile push and a possible future WebSocket adapter all accelerate delivery of the same durable records.

## Event lifecycle

1. A producer completes its domain transaction.
2. It calls `notificationservice::publish()` with a stable idempotency key and explicit recipient user IDs.
3. Notifications stores one immutable event and one private state row per unique recipient.
4. Clients retrieve their own feed using an opaque cursor.
5. Read state changes only on the authenticated recipient's row.
6. Optional channel workers may later deliver the event through Communications.

Producers must never store rendered HTML in a notification. Supply a title, plain-text summary, stable source identity, target URL and a small structured payload.

## Authentication and authorization

Version 1 uses the authenticated Chisimba session. Responses are private and non-cacheable. Every feed, count and mutation derives the user ID from the authenticated session; a caller cannot request another user's feed. State-changing browser requests require the CSRF token rendered by the centre.

A future mobile token authenticator may replace session authentication at the controller boundary without changing resources or domain services.

## Resources

### List notifications

`GET /index.php?module=notifications&action=api_v1_feed&limit=25&cursor=OPAQUE&unread=1`

`limit` is 1–100. `cursor` comes only from `page.nextCursor`; clients must not construct it. `unread=1` is optional.

```json
{"ok":true,"items":[{"id":"recipient-state-id","eventId":"event-id","type":"discussion.reply.created","state":"unread","actorUserId":"user-id","contextCode":"course-code","source":{"type":"discussion_post","id":"post-id"},"title":"New reply","summary":"A learner replied to your topic.","targetUrl":"/index.php?...","payload":{"topicId":"topic-id"},"createdAt":"2026-09-03 12:00:00","readAt":null}],"page":{"nextCursor":null,"hasMore":false}}
```

### Unread count

`GET /index.php?module=notifications&action=api_v1_unread_count`

```json
{"ok":true,"unreadCount":3}
```

### Mark read

`POST /index.php?module=notifications&action=api_v1_mark_read`

Form fields: `notification_id`, `csrf_token`. Repeating the operation is safe.

## Publishing from an application

```php
$notifications = $this->getObject('notificationservice', 'notifications');
$result = $notifications->publish(array(
    'idempotencyKey' => 'discussion-post:' . $postId . ':recipient:' . $userId,
    'type' => 'discussion.reply.created',
    'actorUserId' => $authorId,
    'contextCode' => $contextCode,
    'sourceType' => 'discussion_post',
    'sourceId' => $postId,
    'recipientUserIds' => array($userId),
    'title' => 'New reply in ' . $topicTitle,
    'summary' => $authorName . ' replied to a discussion you follow.',
    'targetUrl' => $postUrl,
    'payload' => array('discussionId' => $discussionId, 'topicId' => $topicId)
));
```

Use one deterministic idempotency key for the logical event/recipient set. Do not include secrets, email addresses, full post bodies or authorization decisions in payloads. Recipients must be resolved from current domain membership and subscription rules.

## Building a web or mobile client

1. Authenticate through the platform-supported session or future token flow.
2. Fetch unread count for the badge.
3. Fetch the first feed page and retain `nextCursor`.
4. Request the next page with that cursor; reset pagination when filters change.
5. Render title and summary as text, never trusted HTML.
6. Use `targetUrl` for web navigation. A mobile client may map `source.type`, `source.id` and payload identifiers to a native route.
7. Mark read only after an explicit user action or after the application has genuinely presented the item.
8. Treat duplicated responses and repeated mark-read operations as normal and safe.

## Extending the system

New producers add namespaced event types such as `assessment.mark.released`. New channel adapters subscribe to stored events and maintain their own delivery attempts; they do not change recipient read state. Retention must preserve operational and educational audit needs while minimising personal data. Provider errors and credentials must never appear in client payloads.

## Discussion integration

Discussion is the first producer. After a topic or reply is stored, `discussionnotificationpublisher` resolves user IDs from the existing discussion and topic subscriptions, removes the author, and publishes `discussion.topic.created` or `discussion.reply.created`. The immutable post ID is the idempotency boundary. Notification payloads contain identifiers and navigation metadata, never the post body. Discussion does not send email itself; a later preference-aware channel worker will project eligible notification events into Communications.

## Toolbar preview and read-token renewal

When the module is installed, the authenticated toolbar offers an Updates bell with an unread count and the five most recent notifications. The preview fetches on opening; the count refreshes every minute while the page is visible and on returning to the page. Reading is explicit: opening the preview or following an update does not mark it read. Close, Escape, and clicking outside dismiss the preview. The full centre supports unread filtering and pagination.

Read responses include a replacement `csrfToken`, including an `invalid_csrf` response. Clients must retain that replacement before the next mutation. After an expired token, the web client asks the user to retry the read action. Read actions update both the preview and centre without navigation. Links in this web client are restricted to HTTP(S) destinations on the current origin; notification text is escaped.

Install Notifications with the normal module catalogue installer before expecting events or toolbar controls. The framework toolbar integration is optional when the module is absent. This feature provides in-app notifications only; email delivery remains the responsibility of Communications. Install the matching module code before enabling the framework integration on another site.
