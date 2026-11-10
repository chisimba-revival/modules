<?php
$tablename='tbl_credit_entries';
$options=['comment'=>'Reusable credit ledger'];
$fields=[
    'id'=>['type'=>'text','length'=>64,'notnull'=>true],
    'account_id'=>['type'=>'text','length'=>64,'notnull'=>true],
    'amount'=>['type'=>'integer','notnull'=>true],
    'kind'=>['type'=>'text','length'=>16,'notnull'=>true],
    'fingerprint'=>['type'=>'text','length'=>64,'notnull'=>true],
    'plan_code'=>['type'=>'text','length'=>32,'notnull'=>false],
    'valid_until'=>['type'=>'timestamp','notnull'=>false],
    'reversal_of'=>['type'=>'text','length'=>64,'notnull'=>false],
    'created_at'=>['type'=>'timestamp','notnull'=>true],
];
$tableIndexes=['credit_entries_pk'=>['unique'=>true,'fields'=>['id'=>[]]],'credit_entries_account'=>['fields'=>['account_id'=>[]]],'credit_entries_reversal'=>['fields'=>['reversal_of'=>[]]]];
