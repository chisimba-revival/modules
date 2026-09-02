<?php
/**
 * Cross-course due-item calendar for the student dashboard.
 *
 * Assessment modules remain the source of truth for activities and results.
 * This read-only service discovers their Gradebook adapters and normalises the
 * small amount of information needed by the dashboard.
 *
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class studentdueitems extends ChisimbaObject
{
    private $user;
    private $userContext;
    private $contexts;
    private $contextModules;
    private $providers;
    private $time;
    private $language;
    private $icons;

    /** Initialise collaborating read-only services. */
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->userContext = $this->getObject('usercontext', 'context');
        $this->contexts = $this->getObject('dbcontext', 'context');
        $this->contextModules = $this->getObject('dbcontextmodules', 'context');
        $this->providers = $this->getObject('assessmentproviderregistry', 'gradebook');
        $this->time = $this->getObject('timeanddateservice', 'timeanddate-service');
        $this->language = $this->getObject('language', 'language');
        $this->icons = $this->getObject('iconservice', 'ui');
    }

    /** Return the complete accessible dashboard calendar markup. */
    public function show()
    {
        if (!$this->user->isLoggedIn()) {
            return '';
        }
        $items = $this->items($this->user->userId());
        return $this->render($items);
    }

    /**
     * Collect and normalise due items from enabled course assessment modules.
     * A broken optional provider is isolated so the dashboard remains usable.
     */
    public function items($userId)
    {
        $items = array();
        $contextCodes = array_values(array_unique((array) $this->userContext->getUserContext($userId)));
        foreach ($contextCodes as $contextCode) {
            $context = $this->contexts->getContextDetails($contextCode);
            if (!is_array($context) || empty($context['title'])) {
                continue;
            }
            $enabled = array_flip((array) $this->contextModules->getContextModules($contextCode));
            foreach ((array) $this->providers->all() as $provider) {
                if (!isset($enabled[$provider['module_id']])) {
                    continue;
                }
                try {
                    $adapter = $this->providers->adapter($provider['key']);
                    if (!$adapter) {
                        continue;
                    }
                    foreach ((array) $adapter->listActivities($contextCode) as $activity) {
                        $item = $this->normalise(
                            $provider,
                            $adapter,
                            $activity,
                            $contextCode,
                            $context['title'],
                            $userId
                        );
                        if ($item !== null) {
                            $items[] = $item;
                        }
                    }
                } catch (Throwable $failure) {
                    // Optional assessment modules must never break My Learning.
                }
            }
        }
        usort($items, static function ($left, $right) {
            return $left['due'] <=> $right['due'];
        });
        return $items;
    }

    /** Normalise one provider-owned activity or exclude it from the calendar. */
    private function normalise($provider, $adapter, $activity, $contextCode, $courseTitle, $userId)
    {
        if (!is_array($activity) || empty($activity['id']) || empty($activity['name'])
            || empty($activity['closing_date']) || !empty($activity['bypass'])) {
            return null;
        }
        $due = $this->time->inTimezone($activity['closing_date']);
        if (!$due instanceof DateTimeImmutable) {
            return null;
        }
        $result = $adapter->getStudentResult($contextCode, $activity['id'], $userId);
        $status = is_array($result) && !empty($result['status'])
            ? (string) $result['status'] : 'not_attempted';
        $target = $adapter->getLaunchTarget($contextCode, $activity['id'], 'learner');
        $url = '';
        if (is_array($target) && !empty($target['module'])) {
            $url = $this->uri((array) ($target['params'] ?? array()), $target['module']);
        }
        return array(
            'id'=>(string) $activity['id'],
            'title'=>(string) $activity['name'],
            'course'=>(string) $courseTitle,
            'provider'=>(string) $provider['key'],
            'providerLabel'=>(string) $provider['label'],
            'due'=>$due,
            'status'=>$status,
            'markPercent'=>is_array($result) && isset($result['mark_percent'])
                && is_numeric($result['mark_percent']) ? (float) $result['mark_percent'] : null,
            'url'=>$url,
        );
    }

    /** Render a compact calendar strip and prioritised upcoming-work agenda. */
    private function render(array $items)
    {
        $e = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $text = function ($key, $fallback) {
            return $this->language->languageText('mod_mylearning_' . $key, 'mylearning', $fallback);
        };
        $zone = new DateTimeZone($this->time->siteTimezone());
        $today = new DateTimeImmutable('today', $zone);
        $visible = array_values(array_filter($items, static function ($item) use ($today) {
            return $item['due'] >= $today->modify('-1 day')
                && $item['due'] < $today->modify('+42 days');
        }));
        $byDate = array();
        foreach ($visible as $item) {
            $byDate[$item['due']->format('Y-m-d')][] = $item;
        }

        $html = '<section class="dashboard-panel student-due-dashboard" aria-labelledby="student-due-title">'
            . '<header class="dashboard-panel__header"><div>'
            . '<p class="dashboard-eyebrow">' . $e($text('calendar_eyebrow', 'Your schedule')) . '</p>'
            . '<h2 id="student-due-title">' . $e($text('calendar_title', 'Coming up')) . '</h2>'
            . '<p>' . $e($text('calendar_intro', 'Due work across all your courses.')) . '</p></div>'
            . '<span class="semantic-pill semantic-pill--info">' . count($visible) . ' '
            . $e($text('calendar_due', 'due')) . '</span></header>';

        $html .= '<div class="dashboard-date-strip" role="list" aria-label="'
            . $e($text('calendar_next_days', 'Next seven days')) . '">';
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $today->modify('+' . $offset . ' days');
            $key = $date->format('Y-m-d');
            $count = isset($byDate[$key]) ? count($byDate[$key]) : 0;
            $html .= '<div class="dashboard-date-chip'
                . ($offset === 0 ? ' dashboard-date-chip--today' : '')
                . ($count > 0 ? ' dashboard-date-chip--active' : '') . '" role="listitem"'
                . ' title="' . $e($count . ' ' . $text('calendar_items', 'items')) . '">'
                . '<span>' . $e($date->format('D')) . '</span><strong>' . $date->format('j') . '</strong>'
                . ($count > 0 ? '<i aria-hidden="true">' . $count . '</i>' : '') . '</div>';
        }
        $html .= '</div><div class="dashboard-agenda">';

        if ($visible === array()) {
            $html .= '<div class="dashboard-empty-state"><span class="dashboard-empty-state__icon" aria-hidden="true">✓</span>'
                . '<div><h3>' . $e($text('calendar_clear', 'Your near-term calendar is clear')) . '</h3>'
                . '<p>' . $e($text('calendar_clear_help', 'New due items will appear here automatically.')) . '</p></div></div>';
        } else {
            foreach (array_slice($visible, 0, 8) as $item) {
                $completed = in_array($item['status'], array('marked', 'completed'), true);
                $overdue = !$completed && $item['due'] < new DateTimeImmutable('now', $zone);
                $tone = $completed ? 'complete' : ($overdue ? 'overdue' : 'upcoming');
                $now = new DateTimeImmutable('now', $zone);
                $days = (int) $now->setTime(0, 0)->diff($item['due']->setTime(0, 0))->format('%r%a');
                $statusLabels = array(
                    'submitted'=>$text('calendar_submitted', 'Submitted'),
                    'marked'=>$text('calendar_marked', 'Marked'),
                    'in_progress'=>$text('calendar_in_progress', 'In progress'),
                    'completed'=>$text('calendar_complete', 'Complete'),
                );
                $statusLabel = $statusLabels[$item['status']] ?? '';
                $html .= '<article class="dashboard-agenda-item dashboard-agenda-item--' . $tone . '">'
                    . '<time datetime="' . $e($item['due']->format(DATE_ATOM)) . '"><strong>'
                    . $e($item['due']->format('j')) . '</strong><span>' . $e($item['due']->format('M')) . '</span></time>'
                    . '<div class="dashboard-agenda-item__body"><span class="dashboard-agenda-item__meta">'
                    . $e($item['course']) . ' · ' . $e($item['providerLabel']) . '</span><h3>'
                    . $e($item['title']) . '</h3><span class="dashboard-agenda-item__due">'
                    . $e($overdue ? $text('calendar_overdue', 'Overdue') : $this->time->formatDateTime($item['due']))
                    . '</span><div class="dashboard-agenda-item__status">';
                if (!$completed && !$overdue) {
                    $html .= '<span class="dashboard-days-badge" title="' . $e($text('calendar_days_remaining', 'Days remaining')) . '"><strong>'
                        . $days . '</strong><small>' . $e($text('calendar_days', 'days')) . '</small></span>';
                }
                if ($statusLabel !== '') {
                    $html .= '<span class="semantic-pill">' . $e($statusLabel) . '</span>';
                }
                if ($item['markPercent'] !== null) {
                    $html .= '<span class="dashboard-mark" title="' . $e($text('calendar_mark', 'Mark')) . '">'
                        . $this->icons->render('percent', array('decorative'=>true)) . '<strong>'
                        . $e(number_format($item['markPercent'], 1)) . '</strong></span>';
                }
                $html .= '</div></div>';
                if ($item['url'] !== '') {
                    $openLabel = $text('calendar_open', 'Open activity');
                    $html .= '<a class="icon-button dashboard-agenda-item__action" href="' . $e($item['url'])
                        . '" aria-label="' . $e($openLabel . ': ' . $item['title'])
                        . '" title="' . $e($openLabel) . '"><span aria-hidden="true">'
                        . $this->icons->render('arrow-right', array('decorative'=>true)) . '</span></a>';
                }
                $html .= '</article>';
            }
        }
        return $html . '</div></section>';
    }
}
?>
