<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class mylearning extends controller
{
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->userContext = $this->getObject('usercontext', 'context');
        $this->contextBlocks = $this->getObject('dbcontextblocks', 'context');
        $this->dynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
    }
    public function dispatch($action)
    {
        $blockAction = in_array($action, array(
            'renderblock', 'addblock', 'removeblock', 'moveblock'
        ), true);
        if ($blockAction && $this->user->isAdmin()) {
            $this->dispatchBlockAction($action);
            return null;
        }
        $managing = $action === 'manage' && $this->user->isAdmin();
        if (!$managing && !$this->mayView()) { return 'noaccess_tpl.php'; }
        $this->setVar('managingDashboard', $managing);
        $this->setVar('learningOverview', $this->getObject('studentlearningoverview', 'context')->show());
        $this->setVar('dueItems', $this->getObject('studentdueitems', 'mylearning')->show());
        $modules=$this->getObject('modules','modulecatalogue');
        $membershipAvailable=!$managing
            &&$modules->checkIfRegistered('membership-service')
            &&$modules->checkIfRegistered('payment-service');
        $this->setVar('membershipAvailable',$membershipAvailable);
        $this->setVar('membershipTier',$membershipAvailable
            ?$this->getObject('membershipservice','membership-service')->effectiveTier($this->user->userId())
            :'free');
        $this->setVar('upperBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'right'
        ));
        $this->setVar('lowerBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'left'
        ));
        $this->setVar('wideBlocks', $this->contextBlocks->getContextBlocks(
            'mylearning', 'middle'
        ));
        $this->setVar('mayEditBlocks', $managing);
        $this->setVar('availableNarrowBlocks', $this->availableBlocks(false));
        $this->setVar('availableWideBlocks', $this->availableBlocks(true));
        return 'main_tpl.php';
    }

    private function dispatchBlockAction($action)
    {
        if (!$this->user->isAdmin()) { return; }
        $blockId = (string) $this->getParam('blockid', '');
        $side = (string) $this->getParam('side', 'left');
        if (!in_array($side, array('left', 'right', 'middle'), true)) {
            return;
        }
        if ($action === 'removeblock') {
            echo $this->contextBlocks->removeBlock($blockId) ? 'ok' : 'notok';
            return;
        }
        if ($action === 'moveblock') {
            $direction = $this->getParam('direction', 'down');
            $result = $direction === 'up'
                ? $this->contextBlocks->moveBlockUp($blockId, 'mylearning')
                : $this->contextBlocks->moveBlockDown($blockId, 'mylearning');
            echo $result ? 'ok' : 'notok';
            return;
        }
        $block = explode('|', $blockId);
        if (count($block) < 3
            || !in_array($block[0], array('block', 'dynamicblock'), true)) {
            return;
        }
        if ($action === 'addblock') {
            $result = $this->contextBlocks->addBlock(
                $blockId, $side, 'mylearning', $block[2]
            );
            echo $result === false ? '' : $result;
            return;
        }
        $domId = $side . '___' . str_replace('|', '___', $blockId);
        if ($block[0] === 'dynamicblock') {
            $content = $this->dynamicBlocks->showBlock($block[1]);
        } else {
            $content = $this->getObject('blocks', 'blocks')->showBlock(
                $block[1], $block[2], null, 20, true, false
            );
        }
        echo '<div id="' . htmlspecialchars($domId, ENT_QUOTES, 'UTF-8')
            . '" class="block highlightblock">' . $content . '</div>';
    }

    private function availableBlocks($wide)
    {
        if (!$this->user->isAdmin()) { return array(); }
        $options = array();
        $dynamic = $wide
            ? $this->dynamicBlocks->getWideSiteBlocks()
            : $this->dynamicBlocks->getSmallSiteBlocks();
        foreach ((array) $dynamic as $block) {
            $title = trim((string) ($block['title'] ?? ''));
            $options[] = array(
                'value' => 'dynamicblock|' . $block['id'] . '|' . $block['module'],
                'title' => ($title === '' ? $block['module'] : $title)
                    . ' (' . $block['module'] . ')',
            );
        }
        $registry = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        $size = $wide ? 'wide' : 'normal';
        foreach ((array) $registry->getBlocks($size, 'site|user|postlogin') as $block) {
            if ($block['moduleid'] === 'contentblocks') { continue; }
            $title = $block['blockname'];
            try {
                $instance = $this->newObject(
                    'block_' . $block['blockname'], $block['moduleid']
                );
                if (trim((string) $instance->title) !== '') {
                    $title = trim((string) $instance->title);
                }
            } catch (Throwable $exception) {
                // A broken optional block must not break page administration.
            }
            $options[] = array(
                'value' => 'block|' . $block['blockname'] . '|'
                    . $block['moduleid'],
                'title' => $title . ' (' . $block['moduleid'] . ')',
            );
        }
        usort($options, static function ($left, $right) {
            return strcasecmp($left['title'], $right['title']);
        });
        return $options;
    }
    private function mayView()
    {
        if (!$this->user->isLoggedIn()) { return false; }
        return count((array) $this->userContext->getContextWhereStudent($this->user->userId())) > 0;
    }
}
?>
