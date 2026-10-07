<?php
/** Shop guides through the shared contextual Help service. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($id)
    { return $id === 'buying' || (in_array($id, ['managing', 'shipping', 'fulfilment'], true) && $this->getObject('shopservice', 'shop')->canManage()); }
    public function getTopic($id)
    {
        if (!$this->mayViewTopic($id)) return null;
        $service = $this->getObject('shopservice', 'shop');
        return ['title' => $service->text('help_' . $id . '_title'), 'summary' => $service->text('help_' . $id . '_summary'),
            'steps' => [$service->text('help_' . $id . '_steps')], 'sections' => []];
    }
}
