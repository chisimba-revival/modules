<?php
/** Public event sections follow occurrence times, including repeat events.
 * @author Derek Keats <derek@dkeats.com>
 */
$GLOBALS['kewl_entry_point_run']=true; class ChisimbaObject {}
require dirname(__DIR__).'/classes/eventservice_class_inc.php';
/** Fixed clock for boundary and ordering checks. @author Derek Keats <derek@dkeats.com> */
class EventCatalogueClock extends eventservice {public function now(){return 1000;}}
$s=new EventCatalogueClock();
$rows=[['id'=>'repeat','occurrences'=>[['starts_at'=>2000,'ends_at'=>2100],['starts_at'=>100,'ends_at'=>200],['starts_at'=>300,'ends_at'=>400]]],['id'=>'undated','occurrences'=>[]],['id'=>'running','occurrences'=>[['starts_at'=>900,'ends_at'=>1001]]],['id'=>'ended','occurrences'=>[['starts_at'=>950,'ends_at'=>1000]]]];
$r=$s->catalogueSections($rows);
if(array_column(array_column($r['upcoming'],'event'),'id')!==['running','repeat','undated'])throw new RuntimeException('Upcoming order incorrect');
if(array_column(array_column($r['past'],'event'),'id')!==['ended','repeat'])throw new RuntimeException('Past order incorrect');
if($r['past'][1]['date']['starts_at']!==300)throw new RuntimeException('Past repeat date must be the latest');
echo "PASS: upcoming/past boundary, running and undated events, recurrence and ordering.\n";
