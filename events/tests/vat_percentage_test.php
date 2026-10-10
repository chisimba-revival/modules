<?php
/** Inclusive VAT calculation and input validation.
 * @author Derek Keats <derek@dkeats.com>
 */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/eventservice_class_inc.php';
foreach([[11500,'15',1500],[10000,'15',1304],[10750,'7.5',750],[9999,'0',0],[1,'100',1],[100000000,'100',50000000]] as [$gross,$rate,$expected]) {
    if(eventservice::inclusiveVat($gross,$rate)!==$expected) throw new RuntimeException('Incorrect inclusive VAT');
}
foreach(['-1','101','15.001','15%','', 'abc'] as $invalid) {
    try {eventservice::inclusiveVat(11500,$invalid);throw new RuntimeException('Invalid rate accepted');}
    catch(DomainException $e) {if($e->getMessage()!=='field:vat_percent:vat_percent_invalid')throw $e;}
}
if(eventservice::vatPercentage(11500,1500)!=='15.00')throw new RuntimeException('Legacy rate inference failed');
echo "PASS: inclusive VAT, fractional percentages, cent rounding, zero VAT, limits and invalid input.\n";
