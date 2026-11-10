<?php
$tablename='tbl_credit_holds';
$options=['comment'=>'Reusable credit ledger'];
$fields=[
    'id'=>['type'=>'text','length'=>64,'notnull'=>true],
    'account_id'=>['type'=>'text','length'=>64,'notnull'=>true],
    'state'=>['type'=>'text','length'=>16,'notnull'=>true],
    'created_at'=>['type'=>'timestamp','notnull'=>true],
    'updated_at'=>['type'=>'timestamp','notnull'=>true],
];
$tableIndexes=['credit_holds_pk'=>['unique'=>true,'fields'=>['id'=>[]]],'credit_holds_account'=>['fields'=>['account_id'=>[]]]];
