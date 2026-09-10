<?php
/**
 * Behaviour checks for graph validation and subgraph scoping.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
$GLOBALS['kewl_entry_point_run']=true;
if(!class_exists('controller')){class controller{}}
require_once dirname(__DIR__).'/classes/knowledgemapgraphservice_class_inc.php';
$service=new knowledgemapgraphservice();
$document=array(
    'rootId'=>'root',
    'nodes'=>array(
        array('id'=>'root','title'=>'Root'),
        array('id'=>'branch','title'=>'Branch','color'=>'#ffffff'),
        array('id'=>'leaf','title'=>'Leaf')
    ),
    'relationships'=>array(
        array('type'=>'contains','from'=>'root','to'=>'branch'),
        array('type'=>'contains','from'=>'branch','to'=>'leaf'),
        array('type'=>'supports','from'=>'leaf','to'=>'root'),
        array('type'=>'links_to','from'=>'leaf','to'=>'example.org')
    )
);
$normal=$service->normalise($document);
$descendants=$service->subgraph($document,'branch','descendants');
$cycle=$document;$cycle['relationships'][]=array('type'=>'contains','from'=>'leaf','to'=>'root');
$checks=array(
    'valid typed graph accepted'=>$normal['valid']&&count($normal['document']['relationships'])===4,
    'external link separated from node endpoint'=>$normal['document']['relationships'][3]['to']===''&&$normal['document']['relationships'][3]['externalTarget']==='https://example.org',
    'presentation retained'=>$normal['document']['nodes'][1]['presentation']['color']==='#ffffff',
    'descendant scope is frozen'=>count($descendants['nodes'])===2&&count($descendants['relationships'])===2&&$descendants['rootId']==='branch',
    'containment cycle rejected'=>!$service->normalise($cycle)['valid']
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}

$media=$document;
$media['nodes'][1]['presentation']=array('mediaIntent'=>'create-video','icon'=>'lucide:clapperboard','offsetX'=>123.5,'offsetY'=>-42,'fontFamily'=>'Georgia','side'=>'left');
$media['relationships'][0]['order']=2;
$saved=$service->normalise($media)['document'];
$again=$service->normalise($saved)['document'];
foreach(array('create-video','use-video','create-text','use-text') as $intent){$media['nodes'][1]['presentation']['mediaIntent']=$intent;$result=$service->normalise($media);if($result['document']['nodes'][1]['presentation']['mediaIntent']!==$intent)throw new Exception('Media purpose lost');}
if($again['nodes'][1]['presentation']!==$saved['nodes'][1]['presentation']||$again['relationships'][0]['order']!==2)throw new Exception('Editor save round trip changed presentation or order');
$media['nodes'][1]['presentation']['mediaIntent']='execute-code';$media['nodes'][1]['presentation']['offsetX']='not-a-number';
$invalid=$service->normalise($media)['document']['nodes'][1]['presentation'];
if(isset($invalid['mediaIntent'])||isset($invalid['offsetX']))throw new Exception('Invalid semantic or position accepted');
echo "PASS: all media purposes, position and order round trips, invalid metadata rejection\n";

$document['nodes'][2]['description']="Attached note\nSecond line <script>plain text</script>";
$document['nodes'][2]['type']='reference';
$roundtrip=$service->normalise($service->normalise($document)['document'])['document'];
if($roundtrip['nodes'][2]['description']!==$document['nodes'][2]['description']||$roundtrip['nodes'][2]['type']!=='reference')throw new Exception('Attached note or link node type lost');
echo "PASS: attached multiline notes and reference nodes survive normalisation\n";
$document['nodes'][1]['presentation']=array('branchGap'=>160);
$spaced=$service->normalise($service->normalise($document)['document'])['document'];
if($spaced['nodes'][1]['presentation']['branchGap']!=160)throw new Exception('Branch spacing lost');
$document['nodes'][1]['presentation']['branchGap']=9000;
if($service->normalise($document)['document']['nodes'][1]['presentation']['branchGap']!=2000)throw new Exception('Branch spacing not bounded');
echo "PASS: branch spacing persistence and bounds\n";
