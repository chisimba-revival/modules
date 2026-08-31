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
