<?php
class dbcontentblocks extends dbTable
{
    public function init()
    {
        parent::init('tbl_contentblocks');
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
