<?php
/** Accessible, API-backed notification preview. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class notificationtoolbar extends ChisimbaObject
{
    public function show()
    {
        $e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $url = fn($action) => $e(html_entity_decode($this->uri(array('action' => $action), 'notifications'), ENT_QUOTES, 'UTF-8'));
        $token = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']->issue('notifications_api_v1');
        $version = (string) max(filemtime(dirname(__DIR__) . '/resources/notifications.js'), filemtime(dirname(__DIR__) . '/resources/notifications.css'));
        return '<link rel="stylesheet" href="' . $e($this->getResourceUri('notifications.css', 'notifications')) . '?v=' . $version . '">'
            . '<script defer src="' . $e($this->getResourceUri('notifications.js', 'notifications')) . '?v=' . $version . '"></script>'
            . '<details class="notification-toolbar" data-notifications-toolbar data-feed-url="' . $url('api_v1_feed') . '" data-count-url="' . $url('api_v1_unread_count') . '" data-read-url="' . $url('api_v1_mark_read') . '" data-csrf="' . $e($token) . '">'
            . '<summary aria-label="Notifications"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg> Updates <span data-notification-count hidden></span></summary>'
            . '<section class="notification-toolbar__panel" aria-label="Recent notifications"><header><strong>Recent updates</strong><div><button type="button" data-refresh>Refresh</button> <button type="button" data-close aria-label="Close notifications">Close</button></div></header>'
            . '<p role="alert" data-notification-error hidden></p><div data-notification-list aria-live="polite"><p>Loading updates…</p></div>'
            . '<footer><a href="' . $url('default') . '">View all notifications</a></footer></section></details>';
    }
}
