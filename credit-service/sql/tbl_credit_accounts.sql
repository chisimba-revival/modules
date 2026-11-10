<?php
$tablename='tbl_credit_accounts';
$options=['comment'=>'Reusable credit ledger'];
$fields=[
    'id'=>['type'=>'text','length'=>64,'notnull'=>true],
    'scope'=>['type'=>'text','length'=>64,'notnull'=>true],
    'owner_id'=>['type'=>'text','length'=>64,'notnull'=>true],
];
$tableIndexes=['credit_accounts_pk'=>['unique'=>true,'fields'=>['id'=>[]]]];
