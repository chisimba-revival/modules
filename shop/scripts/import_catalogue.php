<?php
/** Explicit CLI draft import. Stable source IDs prevent duplicate or overwritten books.
 * Input is a reviewed catalogue manifest, never customer or order records.
 * @author Derek Keats <derek@dkeats.com>
 */
if (PHP_SAPI !== 'cli' || getenv('SHOP_CATALOGUE_IMPORT') !== 'reviewed-drafts' || count($argv) !== 4) exit(64);
[$script, $root, $host, $file] = $argv;
$data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
if (($data['host'] ?? '') !== $host || ($data['currency'] ?? '') !== 'ZAR') throw new RuntimeException('Wrong catalogue destination');
chdir($root); $GLOBALS['kewl_entry_point_run'] = true;
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']=$host; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php'; $engine=new engine();
$site=$engine->getObject('altconfig','config')->getSiteRoot();
if (parse_url($site, PHP_URL_HOST) !== $host) throw new RuntimeException('Wrong site');
$store=$engine->getObject('shopstore','shop');
$result=$store->transaction(function() use($store,$data,$host) {
    $created=[]; $existing=[];
    foreach($data['books'] as $book) {
        if (!preg_match('/^[a-f0-9]{32}$/D',$book['id']) || $book['status']!=='draft' || $book['revision']!==1 || !is_int($book['price_minor']) || $book['price_minor']<=0 || !is_int($book['stock']) || $book['stock']<0) throw new RuntimeException('Invalid draft');
        foreach(['title'=>191,'isbn'=>32,'description'=>20000,'image_url'=>1500] as $field=>$limit) if (!is_string($book[$field]) || mb_strlen($book[$field])>$limit) throw new RuntimeException('Invalid book field');
        if ($book['title']==='') throw new RuntimeException('Missing title');
        if ($book['image_url']!=='') {
            $url=parse_url($book['image_url']);
            if (!$url || ($url['scheme']??'')!=='https' || ($url['host']??'')!==$host || isset($url['user']) || isset($url['pass'])) throw new RuntimeException('Invalid cover');
        }
        $row=array_intersect_key($book,array_flip(['id','title','isbn','description','image_url','price_minor','stock','status','revision']));
        if ($store->one('books',$book['id'])) {$existing[]=$book['id'];continue;}
        $store->add('books',$row); $created[]=$book['id'];
    }
    return ['created'=>$created,'existing_untouched'=>$existing];
});
echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
