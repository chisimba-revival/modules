<?php
$credential=$premium->create(['device_name'=>'Content fixture']);$key=$credential['key'];
$p=$rights->policy();$rights->savePolicy(['revision'=>$p['revision'],'mode'=>'noncommercial','app_updates'=>'1']);
$species=array_values(array_filter($service->all(),fn($s)=>$s['state']!=='archived'))[0]['id'];
$base=['revision'=>0,'kind'=>'image','role'=>'other','state'=>'active','creator'=>'Fixture creator','alt_text'=>'Fixture image','source_url'=>'https://example.org/photo','image_url'=>'https://example.org/photo.jpg','licence'=>'CC-BY-4.0'];
$public=$service->saveMedia($species,md5('tier-public'),$base);
$private=$service->saveMedia($species,md5('tier-premium'),$base+['audience'=>'premium']);
$nc=$service->saveMedia($species,md5('tier-nc'),array_replace($base,['licence'=>'CC-BY-NC-4.0','image_url'=>'https://example.org/nc.jpg']));
$video=$service->saveMedia($species,md5('tier-video'),array_replace($base,['kind'=>'youtube','youtube_id'=>'abcdef12345','licence'=>'YouTube']));
$uploadSpecies=$service->one($species);
$photos->saveBatch($species,['revision'=>(string)$uploadSpecies['revision'],'captions'=>'Private image','creator'=>'Fixture','licence'=>'CC0-1.0','photo_mode'=>'add','audience'=>'premium'],[$png]);
$uploaded=array_values(array_filter($service->mediaFor($species),fn($m)=>!empty($m['_file_id'])&&($m['audience']??'')==='premium'))[0];
$user->admin=false;
$free=$rights->publicExport();$paid=$rights->premiumExport($key);
$find=fn($bundle)=>array_values(array_filter($bundle['species'],fn($s)=>$s['id']===$species))[0]['media'];
$fm=$find($free);$pm=$find($paid);
ggCheck(!array_filter($fm,fn($m)=>$m['audience']==='premium'||$m['kind']==='youtube'),'Free feed excludes private photos and videos');
ggCheck((bool)array_filter($fm,fn($m)=>$m['licence']==='CC-BY-NC-4.0'),'Free edition retains NC');
ggCheck(!array_filter($pm,fn($m)=>str_contains($m['licence'],'-NC-'))&&(bool)array_filter($pm,fn($m)=>$m['kind']==='youtube'),'Premium feed filters NC and includes video links');
ggReject(fn()=>$rights->premiumExport('bad'),'forbidden');
ggReject(fn()=>$photos->read($uploaded['id']),'not_found');
ggCheck($photos->readPremium($uploaded['id'],$key)===$png,'Premium image bytes delivered only after current entitlement check');
ggReject(fn()=>$photos->readPremium($uploaded['id'],'bad'),'forbidden');
ggReject(fn()=>$photos->read($uploaded['id']),'not_found');
$withoutGrant=$tokens->createApiKey('fixture-other','No entitlement',[grassguidepremium::SCOPE]);
ggReject(fn()=>$rights->premiumExport($withoutGrant['key']),'forbidden');

$user->admin=true;
foreach([$public,$private,$nc,$video,$uploaded] as $media){$media['state']='archived';$service->saveMedia($species,$media['id'],$media);}
$premium->revoke(['key_id'=>(string)$credential['id']]);ggReject(fn()=>$rights->premiumExport($key),'forbidden');
echo "PASS premium content: separate feeds, NC filtering, video gating and authenticated access.\n";
