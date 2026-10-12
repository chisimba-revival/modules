<?php
$root=dirname(__DIR__);
foreach(['livechat','rtt'] as $module)if(file_exists($root.'/'.$module))throw new RuntimeException('Retired Java client returned: '.$module);
foreach(['discussion/templates/content/discussion_mindmap.php','discussion/templates/content/discussion_topic_mindmap.php','sitemap/templates/content/sitemap_visual_tpl.php'] as $path){
 $source=file_get_contents($root.'/'.$path);
 if(str_contains($source,'flashfreemind')||!str_contains($source,"getObject('nativeplayer', 'files')->download"))throw new RuntimeException('Original map route missing: '.$path);
}
echo "PASS: unused Java clients removed; legacy map templates preserve original-file access\n";
