<?php

/**
 * Triplestore controller class
 *
 * Class to control the Triplestore module
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  chisimba
 * @package   triplestore
 * @author    Charl van Niekerk <charlvn@charlvn.com>
 * @copyright 2010 Charl van Niekerk
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id: controller.php 16050 2009-12-26 01:34:10Z charlvn $
 * @link      http://avoir.uwc.ac.za/
 */
// security check - must be included in all scripts
if (!
        /**
         * Description for $GLOBALS
         * @global unknown $GLOBALS['kewl_entry_point_run']
         * @name   $kewl_entry_point_run
         */
        $GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Triplestore controller class
 *
 * Class to control the Triplestore module
 *
 * @category  Chisimba
 * @package   triplestore
 * @author    Charl van Niekerk <charlvn@charlvn.com>
 * @copyright 2010 Charl van Niekerk
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   Release: @package_version@
 * @link      http://avoir.uwc.ac.za/
 */
class triplestore extends controller
{
    public $objLanguage;
    private $store;
    private $admin;
    private $csrf;
    private const TOKEN = 'triplestore.manage';

    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->admin = $this->getObject('tripleadmin', 'triplestore');
        $this->store = $this->getObject('dbtriplestore', 'triplestore');
        $this->csrf = $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf'];
    }
    public function requiresLogin($action = null) { return true; }
    private function input($key, $default = ''): string
    {
        $value = $this->getParam($key, $default);
        return is_string($value) ? $value : '';
    }
    private function message($key, $status)
    {
        http_response_code($status);
        $this->setVar('notice', $key);
        return 'message_tpl.php';
    }
    public function dispatch($action)
    {
        if (!$this->admin->mayManage()) return $this->message('forbidden', 403);
        header('Cache-Control: private, no-store');
        $action = strtolower((string) $action);
        if (in_array($action, ['save', 'delete'], true)) return $this->write($action === 'delete');
        if (in_array($action, ['edit','add','confirmdelete'], true)) {
            $id = $action === 'add' ? '' : $this->input('id');
            $row = $id === '' ? [] : ($this->store->getTriples(['id'=>$id])[0] ?? null);
            if ($row === null || ($action === 'confirmdelete' && !$row)) return $this->message('missing', 404);
            return $this->form($row, $action === 'confirmdelete');
        }
        // Old vendor-specific mutation/upload endpoints are deliberately unavailable.
        if (!in_array($action, ['', 'home','search','view'], true)) return $this->message('retired', 410);
        $filters = [];
        foreach (['id','userid','subject','predicate','object'] as $field) {
            $value = $this->input($field);
            if ($value !== '') $filters[$field] = $value;
        }
        $data = $this->store->browse($filters, (int) $this->input('page', '1'));
        if ($action === 'view') {
            $format = $this->input('format', 'flat');
            if (!in_array($format, ['flat','nested'], true)) return $this->message('invalid', 400);
            $rows = $data['rows'];
            if ($format === 'nested') {
                $rows = [];
                foreach ($data['rows'] as $row) $rows[$row['subject']][$row['predicate']][$row['id']] = $row['object'];
            }
            header('Content-Type: application/json; charset=UTF-8');
            header('X-Total-Count: '.$data['total']);
            header('X-Page: '.$data['page']);
            echo json_encode($rows, JSON_INVALID_UTF8_SUBSTITUTE);
            return null;
        }
        $this->setVar('filters', $filters);
        $this->setVar('data', $data);
        $this->setVar('notice', $this->input('saved') === '1' ? 'saved' : '');
        return 'triplestore_tpl.php';
    }
    private function form(array $row, bool $deleting = false, string $notice = '')
    {
        $this->setVar('triple', $row);
        $this->setVar('deleting', $deleting);
        $this->setVar('notice', $notice);
        $this->setVar('revision', $row['_revision'] ?? ($row ? tripleadmin::revision($row) : ''));
        $this->setVar('token', $this->csrf->issueForSession(self::TOKEN));
        return 'editform_tpl.php';
    }
    private function write(bool $deleting)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return $this->message('postonly', 405);
        $row = ['id'=>$this->input('id'), '_revision'=>$this->input('revision')];
        foreach (['subject','predicate','object'] as $field) $row[$field] = $this->input($field);
        if (!$this->csrf->consume(self::TOKEN, $this->input('csrf_token'))) {
            http_response_code(403);
            return $this->form($row, $deleting, 'expired');
        }
        try {
            $result = $this->admin->write($row['id'], $row, $row['_revision'], $deleting);
        } catch (Throwable $error) {
            // Do not log submitted relationship content or database exception values.
            error_log('Triple Store administration write failed: '.get_class($error));
            $result = 'failed';
        }
        if ($result === 'saved') return $this->nextAction('home', ['saved'=>'1']);
        http_response_code($result === 'conflict' ? 409 : ($result === 'failed' ? 500 : 400));
        return $this->form($row, $deleting, $result);
    }
}
