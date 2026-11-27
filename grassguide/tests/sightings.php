<?php
$user->admin=true;$credential=$premium->create(['device_name'=>'Sightings fixture']);$key=$credential['key'];$user->admin=false;
$record=['id'=>md5('offline-sighting'),'species_id'=>$species,'list'=>'Home walk','notes'=>'Flowering','observed_at'=>time(),'location'=>['latitude'=>-25.0,'longitude'=>31.0,'accuracy'=>7.0]];
$call=fn($records,$token=null)=>$premium->sightingsResponse('POST',$token??$key,json_encode(['sightings'=>$records]));
$r=$call([$record]);ggCheck($r['status']===200&&in_array($species,json_decode($r['body'],true)['ids'],true),'Sightings add species to main checklist');
$r=$call([$record]);ggCheck(count(json_decode($r['body'],true)['sightings'])===1,'Lost response retry does not duplicate sightings');
$changed=$record;$changed['list']='Different';ggCheck($call([$changed])['status']===422,'Immutable ID collision rejected');
$bad=$record;$bad['id']=md5('invalid-coordinates');$bad['location']['latitude']=95;ggCheck($call([$bad])['status']===422,'Invalid coordinates rejected');
ggCheck($call([],'bad')['status']===401&&$premium->sightingsResponse('GET',$key,'')['status']===405,'Sightings authenticated POST only');
$other=$tokens->createApiKey('fixture-other','Content other',[grassguidepremium::SCOPE]);ggCheck($call([],$other['key'])['status']===403,'Nonpremium cannot read locations');

$secondRecord=$record;$secondRecord['id']=md5('second-trip-sighting');$secondRecord['list']='Second trip';$secondRecord['location']=null;
ggCheck($call([$secondRecord])['status']===200,'Repeat species can be recorded in another project without coordinates');
$before=json_decode($call([])['body'],true);
$invalid=$record;$invalid['id']=md5('bad-species');$invalid['species_id']=str_repeat('f',32);
$new=$record;$new['id']=md5('rollback-sighting');
ggCheck($call([$new,$invalid])['status']===422,'Invalid batch rejected');
ggCheck(json_decode($call([])['body'],true)===$before,'Failed batch preserves all sightings and checklist');
ggCheck(!str_contains(json_encode($rights->publicExport()),'Home walk'),'Private projects absent from public catalogue');
$store->syncChecklist('sighting-other-user',md5('isolated-device'),[]);
ggCheck($store->syncSightings('sighting-other-user',[])['sightings']===[],'Accounts never share sightings');
ggCheck($premium->sightingsResponse('OPTIONS','','')['status']===204,'Location sync CORS preflight works');
$user->admin=true;$premium->revoke(['key_id'=>(string)$credential['id']]);
ggCheck($call([])['status']===401,'Revoked credentials cannot read sightings');
echo "PASS private sightings: checklist merge, repeats, retries, rollback, location validation, account isolation and revoked access.\n";
