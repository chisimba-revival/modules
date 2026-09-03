<?php
/**
 * Secure course-content activity provider for Discussion.
 *
 * @package discussion
 * @author Derek Keats
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class modulelinks_discussion extends ChisimbaObject
{
    /** @var object */
    private $discussions;
    /** @var object */
    private $topics;
    /** @var object */
    private $user;

    /** Initialise bounded read dependencies. */
    public function init()
    {
        $this->discussions = $this->getObject('dbdiscussion', 'discussion');
        $this->topics = $this->getObject('dbtopic', 'discussion');
        $this->user = $this->getObject('user', 'security');
    }

    /** Return a small navigation tree for the active course. */
    public function show()
    {
        $this->loadClass('treenode', 'tree');
        return new treenode(array(
            'link' => $this->uri(array(), 'discussion'),
            'text' => 'Discussion',
        ));
    }

    /**
     * Return canonical discussions and topics addable to course content.
     * Replies are mutable conversation records and are deliberately excluded.
     *
     * @param string $contextCode Course identifier supplied by contextcontent.
     * @return array
     */
    public function getContextLinks($contextCode)
    {
        $contextCode = trim((string) $contextCode);
        if (!$this->validIdentifier($contextCode) || $contextCode === 'root') {
            return array();
        }
        $links = array();
        $discussions = $this->discussions->getContextDiscussions($contextCode);
        if (!is_array($discussions)) {
            return $links;
        }
        foreach ($discussions as $discussion) {
            if (!$this->validIdentifier($discussion['id'])) {
                continue;
            }
            $links[] = $this->activity(
                $discussion['discussion_name'],
                $discussion['discussion_description'],
                'discussion:' . $discussion['id'],
                array('action' => 'discussion', 'id' => $discussion['id'])
            );
            $topics = $this->topics->showTopicsInDiscussion(
                $discussion['id'],
                $this->user->userId()
            );
            if (!is_array($topics)) {
                continue;
            }
            foreach ($topics as $topic) {
                if (!$this->validIdentifier($topic['topic_id'])) {
                    continue;
                }
                $title = stripslashes((string) $topic['post_title']);
                $links[] = $this->activity(
                    'Topic: ' . $title,
                    'Open this topic in ' . $discussion['discussion_name'] . '.',
                    'topic:' . $topic['topic_id'],
                    array('action' => 'viewtopic', 'id' => $topic['topic_id'])
                );
            }
        }
        return $links;
    }

    /** Build the legacy-compatible activity descriptor. */
    private function activity($title, $description, $itemId, array $params)
    {
        return array(
            'menutext' => trim(strip_tags((string) $title)),
            'description' => trim(strip_tags((string) $description)),
            'itemid' => $itemId,
            'moduleid' => 'discussion',
            'params' => $params,
        );
    }

    /** Accept only identifiers safe for routing and database lookup. */
    private function validIdentifier($value)
    {
        return preg_match('/^[A-Za-z0-9_-]{1,128}$/', (string) $value) === 1;
    }
}
