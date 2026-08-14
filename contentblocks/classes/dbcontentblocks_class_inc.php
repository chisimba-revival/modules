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
 * Database gateway for reusable content blocks.
 *
 * @category Chisimba
 * @package  contentblocks
 * @author   Derek Keats
 */
class dbcontentblocks extends dbTable
{
    /**
     * Initialise the content-block database gateway.
     *
     * The signature deliberately matches dbTable::init() for PHP 8 compatibility.
     *
     * @param string|null     $tableName     Parent-compatible table-name argument
     * @param mixed           $pearDb        Existing database connection, when supplied
     * @param callable|string $errorCallback Database error callback
     *
     * @return void
     */
    public function init($tableName = null, $pearDb = NULL, $errorCallback = "globalPearErrorCallback")
    {
        parent::init('tbl_contentblocks', $pearDb, $errorCallback);
    }

    public function find($id)
    {
        foreach ($this->getAll() as $row) {
            if ((string)$row['id'] === (string)$id && ($row['deleted'] ?? '0') !== '1') {
                return $row;
            }
        }
        return false;
    }

    public function findByKey($key)
    {
        foreach ($this->getAll() as $row) {
            if ((string)$row['blockkey'] === (string)$key && ($row['deleted'] ?? '0') !== '1') {
                return $row;
            }
        }
        return false;
    }

    public function forScope($scope, $contextCode = '')
    {
        $rows = array();
        foreach ($this->getAll('ORDER BY datemodified DESC') as $row) {
            if (($row['deleted'] ?? '0') === '1' || ($row['scope'] ?? '') !== $scope) {
                continue;
            }
            if ($scope === 'context' && ($row['contextcode'] ?? '') !== $contextCode) {
                continue;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Return one block using the legacy Content blocks contract.
     *
     * @param string $id Content block identifier
     *
     * @return array|false Block row or false when it does not exist
     */
    public function getBlock($id)
    {
        $rows = $this->getBlockById($id);
        return $rows ? $rows[0] : false;
    }

    /**
     * Return a block in the array shape expected by legacy consumers.
     *
     * @param string $id Content block identifier
     *
     * @return array List containing zero or one block
     */
    public function getBlockById($id)
    {
        $row = $this->find($id);
        return $row ? array($this->legacyRow($row)) : array();
    }

    /**
     * Return site blocks in the array shape expected by prelogin/postlogin.
     *
     * @param string $blockType Legacy type: content_text or content_widetext
     *
     * @return array|null Matching blocks, or null when none exist
     */
    public function getBlocksArr($blockType)
    {
        $width = $blockType === 'content_widetext' ? 'wide' : 'side';
        $rows = array();
        foreach ($this->forScope('site') as $row) {
            $rowWidth = ($row['blockwidth'] ?? '') === 'normal' ? 'side' : ($row['blockwidth'] ?? '');
            if ($rowWidth === $width) {
                $rows[] = $this->legacyRow($row);
            }
        }
        return $rows ?: null;
    }

    /**
     * Add legacy aliases without changing the new database schema.
     *
     * @param array $row New Content blocks row
     *
     * @return array Row with legacy aliases
     */
    private function legacyRow(array $row)
    {
        $wide = ($row['blockwidth'] ?? '') === 'wide';
        $row['blockid'] = $wide ? 'content_widetext' : 'content_text';
        $row['blocktext'] = $row['body_html'] ?? '';
        $row['css_id'] = '';
        $row['css_class'] = '';
        return $row;
    }
    public function saveBlock(array $data, $userId, $id = '')
    {
        $now = date('Y-m-d H:i:s');
        if ($id !== '') {
            $old = $this->find($id);
            if (!$old) {
                return false;
            }
            $data['modifierid'] = $userId;
            $data['datemodified'] = $now;
            $this->update('id', $id, $data);
            return $this->find($id);
        }
        $id = md5(uniqid((string)mt_rand(), true));
        $data['id'] = $id;
        $data['blockkey'] = $data['blocktype'] . '-' . substr($id, 0, 12);
        $data['creatorid'] = $userId;
        $data['modifierid'] = $userId;
        $data['datecreated'] = $now;
        $data['datemodified'] = $now;
        $data['deleted'] = '0';
        $this->insert($data);
        return $this->find($id);
    }

    public function softDelete($id, $userId)
    {
        return $this->update('id', $id, array(
            'deleted' => '1',
            'modifierid' => $userId,
            'datemodified' => date('Y-m-d H:i:s'),
        ));
    }
}
?>
