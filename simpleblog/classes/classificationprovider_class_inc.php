<?php
/** Bridge shared classification to the canonical SimpleBlog access policy. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class classificationprovider extends ChisimbaObject
{
    public function init() {}

    /** No content or labels are returned until the owning module has checked the record. */
    public function classificationAccess($id)
    {
        $post = $this->getObject('publishingstore','simpleblog')->post($id);
        if (!$post) return null;
        $policy = $this->getObject('publishingpolicy','simpleblog');
        return ['scope_type'=>$post['post_type'], 'scope_id'=>$post['blogid'],
            'read'=>(bool)$policy->canRead($post), 'edit'=>(bool)$policy->canEdit($post)];
    }
}
