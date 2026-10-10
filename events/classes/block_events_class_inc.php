<?php
/**
 * Public event cards for home pages and other block regions.
 * @package events
 * @author Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class block_events extends ChisimbaObject
{
    public $title;
    public $wrapStr = false;
    public $configData;

    public function init()
    {
        $this->title = $this->getObject('eventservice', 'events')->text('heading');
    }

    /** Only the public catalogue is used, including on anonymous home pages. */
    public function show()
    {
        $eventService = $this->getObject('eventservice', 'events');
        $sections = $eventService->catalogueSections($eventService->catalogue());
        $esc = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $t = fn($key) => $esc($eventService->text($key));
        $url = fn($action, $params = []) => $esc($eventService->url($action, $params));
        ob_start();
        require dirname(__DIR__).'/templates/content/eventsblock_tpl.php';
        return ob_get_clean();
    }
}
