<?php
$user->admin=true;
$species=$service->save(bin2hex(random_bytes(16)),['revision'=>'0','genus'=>'Contactus','epithet'=>'fixture','state'=>'draft','taxonomy_review'=>'pending','ecology_review'=>'pending']);
$base=['revision'=>'0','kind'=>'image','role'=>'habit','state'=>'active','source_url'=>'https://example.org/contact','image_url'=>'https://example.org/contact.jpg','alt_text'=>'Fixture','creator'=>'Contact fixture <script>','licence'=>'CC-BY-NC-4.0'];
$first=$service->saveMedia($species['id'],bin2hex(random_bytes(16)),$base);
$second=$service->saveMedia($species['id'],bin2hex(random_bytes(16)),array_merge($base,['image_url'=>'https://example.org/second.jpg']));
$service->saveMedia($species['id'],bin2hex(random_bytes(16)),array_merge($base,['state'=>'archived']));
$service->saveMedia($species['id'],bin2hex(random_bytes(16)),array_merge($base,['licence'=>'CC-BY-4.0']));
$find=fn()=>array_values(array_filter($rights->ncContributors(),fn($g)=>$g['creator']===$base['creator']))[0]??null;
$g=$find();ggCheck(count($g['items'])===2 && count($g['photos'])===2 && count($g['species'])===1 && $g['needs_permission']===2,'Contributors group exact credits and only active NC images');
$item=$rights->target('media:'.$first['id']);$rights->saveTask($item['target'],array_merge($item['task'],['fingerprint'=>$item['fingerprint'],'decision'=>'commercial_permission','permission_evidence'=>'PRIVATE CONTACT EVIDENCE','public_credit'=>'Fixture permission']));
ggCheck($find()['needs_permission']===1 && count($find()['items'])===2,'NC originals remain visible after permission with updated clearance count');
foreach(['=HYPERLINK("https://example.org")','+formula','-formula','@formula',"\tformula",'  =formula'] as $cell)ggCheck(str_starts_with(grassguiderights::csvCell($cell),"'"),'CSV formula guard');
ggCheck(grassguiderights::csvCell('Derek Keats')==='Derek Keats','Normal CSV names preserved');
foreach(['contributors','contributorsexport'] as $action){$user->admin=false;$_GET=['action'=>$action];$_POST=[];http_response_code(200);$c=new grassguide();ggCheck($c->dispatch()==='denied_tpl.php'&&http_response_code()===403,'Contributor admin boundary');}
ggReject(fn()=>$rights->ncContributors(),'forbidden');$user->admin=true;
$_GET=['action'=>'contributors','contributor'=>$find()['id']];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);$c=new grassguide();$template=$c->dispatch();ob_start();$c->render($template);$html=ob_get_clean();
ggCheck(str_contains($html,'Contact fixture &lt;script&gt;')&&!str_contains($html,'PRIVATE CONTACT EVIDENCE')&&!preg_match('/mod_grassguide_[a-z_]+/',$html),'Contributor view escapes credits and excludes private evidence');
$service->setMembership($species['id'],['revision'=>$species['revision'],'operation'=>'remove']);ggCheck($find()===null,'Archived species excluded from contributors');
echo "PASS NC contributors: exact credit grouping, active image scope, original NC permission status, formula protection, private evidence exclusion and administrator boundary.\n";
