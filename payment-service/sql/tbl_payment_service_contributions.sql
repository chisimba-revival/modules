<?php
/** Guest contribution orders. Private contact and capability data never enter public URLs. @author Derek Keats */
$tablename='tbl_payment_service_contributions';
$options=array('comment'=>'Immutable guest contribution order snapshots','collate'=>'utf8_general_ci','character_set'=>'utf8');
$fields=array(
'id'=>array('type'=>'text','length'=>32,'notnull'=>true),
'access_hash'=>array('type'=>'text','length'=>64,'notnull'=>true),
'email'=>array('type'=>'text','length'=>254,'notnull'=>true),
'name'=>array('type'=>'text','length'=>191,'notnull'=>true),
'product_code'=>array('type'=>'text','length'=>96,'notnull'=>true),
'product_name'=>array('type'=>'text','length'=>191,'notnull'=>true),
'price_version'=>array('type'=>'text','length'=>64,'notnull'=>true),
'amount_minor'=>array('type'=>'integer','notnull'=>true),
'vat_minor'=>array('type'=>'integer','notnull'=>true),
'currency'=>array('type'=>'text','length'=>3,'notnull'=>true),
'created_at'=>array('type'=>'timestamp','notnull'=>true)
);
$tableIndexes=array('contribution_primary'=>array('primary'=>true,'fields'=>array('id'=>array())),'contribution_access'=>array('unique'=>true,'fields'=>array('access_hash'=>array())));
