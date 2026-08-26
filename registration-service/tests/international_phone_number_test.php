<?php
$GLOBALS['kewl_entry_point_run']=true;
if(!class_exists('ChisimbaObject')){class ChisimbaObject{}}
require_once dirname(__DIR__).'/classes/internationalphonenumber_class_inc.php';
$phones=new internationalphonenumber();
$cases=array(
    array('+27','082 123 4567','+27821234567'),
    array('+44','07700 900123','+447700900123'),
    array('+1','(415) 555-2671','+14155552671'),
    array('+27','+353 85 123 4567','+353851234567'),
    array('+27','0044 7700 900123','+447700900123'),
);
foreach($cases as $case){$actual=$phones->normalize($case[0],$case[1]);if($actual!==$case[2])throw new RuntimeException($case[1].' normalized as '.var_export($actual,true));}
foreach(array(array('+27','082-CALL-ME'),array('+999','0123456789'),array('+27','123')) as $case){if($phones->normalize($case[0],$case[1])!==null)throw new RuntimeException('Invalid number accepted: '.$case[1]);}
if($phones->defaultCallingCode('+44')!=='+44'||$phones->defaultCallingCode('bad')!=='+27')throw new RuntimeException('Configured calling-code fallback failed.');
echo "PASS: international registration phone normalization\n";
?>
