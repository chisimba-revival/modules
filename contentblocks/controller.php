<?php
/**
 * Content blocks module component.
 *
 * This file forms part of the Chisimba Content blocks module.
 *
 * PHP version 8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
/**
 * Controls site and course content-block administration.
 *
 * @category Chisimba
 * @package  contentblocks
 * @author   Derek Keats
 */
class contentblocks extends controller
{
    private $db;
    private $user;
    private $contextCode = '';

    public function init()
    {
        $this->db = $this->getObject('dbcontentblocks', 'contentblocks');
        $this->user = $this->getObject('user', 'security');
        try {
            $this->contextCode = (string)$this->getObject('dbcontext', 'context')->getContextCode();
        } catch (Throwable $e) {
            $this->contextCode = '';
        }
        $this->setLayoutTemplate('layout_tpl.php');
    }

    public function requiresLogin($action)
    {
        return true;
    }

    private function text($key)
    {
        return $this->getObject('language', 'language')->languageText('mod_contentblocks_' . $key, 'contentblocks');
    }

    private function canManage($scope)
    {
        if ($scope === 'context' && $this->contextCode === '') {
            return false;
        }
        if ($this->user->isAdmin()) {
            return true;
        }
        return $scope === 'context' && $this->contextCode !== ''
            && $this->user->isContextLecturer($this->user->userId(), $this->contextCode);
    }

    private function canManageRow(array $row)
    {
        if ($this->user->isAdmin()) {
            return true;
        }
        return ($row['scope'] ?? '') === 'context'
            && ($row['contextcode'] ?? '') === $this->contextCode
            && $this->canManage('context');
    }

    private function scope()
    {
        $scope = (string)$this->getParam('scope', $this->contextCode !== '' ? 'context' : 'site');
        return $scope === 'context' ? 'context' : 'site';
    }

    private function token()
    {
        $token = (string)$this->getSession('contentblocks_csrf');
        if ($token === '') {
            $token = bin2hex(random_bytes(24));
            $this->setSession('contentblocks_csrf', $token);
        }
        return $token;
    }

    private function validPost()
    {
        $expected = (string)$this->getSession('contentblocks_csrf');
        $actual = (string)$this->getParam('csrf_token', '');
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $expected !== '' && hash_equals($expected, $actual);
    }

    private function flash($message)
    {
        $this->setSession('contentblocks_flash', $message);
    }

    private function redirectManage($scope)
    {
        return $this->nextAction('manage', array('scope' => $scope), 'contentblocks');
    }

    private function safeUrl($value)
    {
        $value = trim((string)$value);
        if ($value === '' || str_starts_with($value, '/')) {
            return $value;
        }
        $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https'), true) ? $value : false;
    }

    public function dispatch($action)
    {
        switch ($action) {
            case 'save': return $this->save();
            case 'delete': return $this->delete();
            case 'manage':
            default: return $this->manage();
        }
    }

    private function manage()
    {
        $scope = $this->scope();
        if (!$this->canManage($scope)) {
            $this->setVar('contentblocksDenied', true);
        }
        $edit = false;
        $id = (string)$this->getParam('id', '');
        if ($id !== '') {
            $edit = $this->db->find($id);
            if ($edit && !$this->canManageRow($edit)) {
                $edit = false;
            }
        }
        $labels = array();
        foreach (array('title','intro','siteblocks','contextblocks','new','edit','delete','save','cancel','type','hero','herodesc','information','informationdesc','width','wide','normal','blocktitle','showtitle','body','imageurl','imagehelp','chooseimage','removeimage','actionlabel','actionurl','key','empty','forbidden','confirmdelete') as $key) {
            $labels[$key] = $this->text($key);
        }
        $this->setVar('contentblocksLabels', $labels);
        $this->setVar('contentblocksScope', $scope);
        $this->setVar('contentblocksContextCode', $this->contextCode);
        $this->setVar('contentblocksRows', $this->canManage($scope) ? $this->db->forScope($scope, $this->contextCode) : array());
        $this->setVar('contentblocksEdit', $edit);
        $this->setVar('contentblocksCsrf', $this->token());
        $this->setVar('contentblocksFlash', (string)$this->getSession('contentblocks_flash'));
        $this->setSession('contentblocks_flash', '');
        return 'manage_tpl.php';
    }

    private function save()
    {
        $scope = $this->scope();
        if (!$this->validPost() || !$this->canManage($scope)) {
            $this->flash($this->text('forbidden'));
            return $this->redirectManage($scope);
        }
        $id = (string)$this->getParam('id', '');
        $old = $id !== '' ? $this->db->find($id) : false;
        if ($old && !$this->canManageRow($old)) {
            $this->flash($this->text('forbidden'));
            return $this->redirectManage($scope);
        }
        if ($old) {
            $scope = $old['scope'];
        }
        $type = (string)$this->getParam('blocktype', 'information');
        $width = (string)$this->getParam('blockwidth', 'wide');
        $title = trim((string)$this->getParam('title', ''));
        $image = $this->safeUrl($this->getParam('image_url', ''));
        $action = $this->safeUrl($this->getParam('action_url', ''));
        $actionLabel = trim((string)$this->getParam('action_label', ''));
        if ($type === 'hero') {
            $width = 'wide';
        } else {
            $image = '';
            $actionLabel = '';
            $action = '';
        }
        if ($title === '' || !in_array($type, array('hero','information'), true) || !in_array($width, array('wide','normal'), true) || $image === false || $action === false) {
            $this->flash($this->text('invalid'));
            return $this->redirectManage($scope);
        }
        $row = $this->db->saveBlock(array(
            'scope' => $scope,
            'contextcode' => $scope === 'context' ? $this->contextCode : '',
            'blocktype' => $old ? $old['blocktype'] : $type,
            'blockwidth' => $old ? $old['blockwidth'] : $width,
            'title' => $title,
            'body_html' => (string)$this->getParam('body_html', ''),
            'image_url' => $image,
            'action_label' => $actionLabel,
            'action_url' => $action,
            'show_title' => $this->getParam('show_title', '') === '1' ? '1' : '0',
        ), $this->user->userId(), $id);
        if (!$row) {
            $this->flash($this->text('savefailed'));
            return $this->redirectManage($scope);
        }
        if (!$old) {
            $registry = $this->getObject('dbmoduleblocks', 'modulecatalogue');
            $registry->addBlock('contentblocks', $row['blockkey'], $row['blockwidth'], $row['scope'] === 'context' ? 'context' : 'site');
        }
        $this->flash($this->text('saved'));
        return $this->redirectManage($scope);
    }

    private function delete()
    {
        $scope = $this->scope();
        $row = $this->db->find((string)$this->getParam('id', ''));
        if (!$this->validPost() || !$row || !$this->canManageRow($row)) {
            $this->flash($this->text('forbidden'));
            return $this->redirectManage($scope);
        }
        $this->db->softDelete($row['id'], $this->user->userId());
        $this->getObject('dbmoduleblocks', 'modulecatalogue')->deleteBlock('contentblocks', $row['blockkey']);
        $this->flash($this->text('deleted'));
        return $this->redirectManage($row['scope']);
    }
}
?>
