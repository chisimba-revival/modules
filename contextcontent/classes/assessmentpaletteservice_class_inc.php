<?php
/**
 * Discover, validate, and render provider-backed course assessments.
 *
 * @author Derek Keats
 */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class assessmentpaletteservice extends ChisimbaObject
{
    public function all($contextCode)
    {
        try {
            $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
        } catch (Throwable $failure) {
            return array();
        }
        $contextModules = $this->getObject('dbcontextmodules', 'context');
        $enabledModules = array_map(
            'strtolower',
            (array) $contextModules->getContextModules($contextCode)
        );
        $groups = array();
        foreach ($registry->all() as $provider) {
            if (!in_array(strtolower($provider['module_id']), $enabledModules, true)) {
                continue;
            }
            $adapter = $registry->adapter($provider['key']);
            if (!is_object($adapter)
                || !is_callable(array($adapter, 'listActivities'))
                || !is_callable(array($adapter, 'getLaunchTarget'))) {
                continue;
            }
            $activities = array();
            foreach ((array) $adapter->listActivities($contextCode) as $activity) {
                if (empty($activity['id']) || !isset($activity['name'])) { continue; }
                $activities[] = array(
                    'id' => (string) $activity['id'],
                    'name' => (string) $activity['name'],
                );
            }
            if ($activities !== array()) {
                $groups[] = array('provider' => $provider, 'activities' => $activities);
            }
        }
        return $groups;
    }

    public function selection($providerKey, $activityId, $contextCode)
    {
        foreach ($this->all($contextCode) as $group) {
            if ($group['provider']['key'] !== (string) $providerKey) { continue; }
            foreach ($group['activities'] as $activity) {
                if ($activity['id'] === (string) $activityId) {
                    return array('provider' => $group['provider'], 'activity' => $activity);
                }
            }
        }
        return false;
    }

    public function render(array $page, $contextCode, $author = false)
    {
        $providerKey = (string) ($page['providermodule'] ?? '');
        $activityId = (string) ($page['provideritemid'] ?? '');
        $selection = $this->selection($providerKey, $activityId, $contextCode);
        if ($selection === false) { return ''; }
        $registry = $this->getObject('assessmentproviderregistry', 'gradebook');
        $adapter = $registry->adapter($providerKey);
        $target = $adapter->getLaunchTarget($contextCode, $activityId, $author ? 'author' : 'learner');
        $e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
        $language = $this->getObject('language', 'language');
        $html = '<section class="chisimba-guidance-card contextcontent-assessment-card"><p class="semantic-pill">'
            . $e($selection['provider']['label']) . '</p><h2>'
            . $e($selection['activity']['name']) . '</h2><p>'
            . $e($selection['provider']['description']) . '</p>';
        if (is_array($target) && !empty($target['module'])) {
            $url = $this->uri(isset($target['params']) ? $target['params'] : array(), $target['module']);
            $html .= '<div class="chisimba-guidance-card__footer"><a class="button" href="'
                . $e($url) . '"><span>' . $e($language->languageText(
                    $author ? 'mod_contextcontent_manageassessment' : 'mod_contextcontent_openassessment',
                    'contextcontent'
                )) . '</span></a></div>';
        }
        return $html . '</section>';
    }
}
?>
