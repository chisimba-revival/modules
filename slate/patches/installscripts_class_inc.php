<?php
/** Preserve identifiable legacy URL placements during normal catalogue upgrades. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class slate_installscripts extends dbTable
{
    public function postinstall($version = null)
    {
        $pages = $this->getObject('dbslatepages','slate');
        $mapping = [];
        foreach (array_merge([['id'=>null,'page'=>'']], $pages->getPages()) as $page) {
            $key = $page['id'] === null ? md5('slate:default') : md5('slate:page:'.$page['id']);
            $params = $page['id'] === null ? [] : ['page'=>$page['page']];
            foreach ([$params, ['action'=>'view']+$params] as $route) {
                // CLI catalogue updates must not hash the operator script's PHP_SELF.
                $url = rtrim($this->getObject('altconfig','config')->getSiteRoot(), '/').'/index.php?'
                    . http_build_query(['module'=>'slate']+$route, '', '&', PHP_QUERY_RFC3986);
                $mapping[md5($url)] = $key;
            }
        }
        $pages->migratePageKeys($mapping);
    }
}
