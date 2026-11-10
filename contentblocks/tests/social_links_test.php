<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module=''){return $GLOBALS['services'][$name];}}
$framework=getenv('CHISIMBA_FRAMEWORK_ROOT')?:dirname(__DIR__,3).'/framework';
require $framework.'/app/core_modules/utilities/classes/richtextsanitizer_class_inc.php';
require $framework.'/app/core_modules/ui/classes/iconservice_class_inc.php';
require dirname(__DIR__).'/classes/sociallinksrenderer_class_inc.php';
$GLOBALS['services']=['richtextsanitizer'=>new richtextsanitizer(),'iconservice'=>new iconservice()];
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$s=new sociallinksrenderer();
$in='<ul class="chisimba-social-links"><li><a href="https://www.facebook.com/one">www.facebook.com</a></li><li><a href="https://www.facebook.com/two">www.facebook.com</a></li><li><a href="https://facebook.com.evil.example/">Other website</a></li></ul>';
$clean=$GLOBALS['services']['richtextsanitizer']->cleanHtml($in);
check($GLOBALS['services']['richtextsanitizer']->cleanHtml($clean)===$clean,'Editable social marker survives sanitisation');
$out=$s->render($clean);
check(substr_count($out,'chisimba-icon--brand-facebook')===2,'Exact host brand matching');
check(str_contains($out,'Facebook — /one')&&str_contains($out,'Facebook — /two'),'Account labels distinct');
check(str_contains($out,'chisimba-icon--globe'),'Unknown host uses globe');
check(str_contains($out,'aria-label="Other website"'),'Custom label preserved');
check(!str_contains($s->render('<ul><li><a href="https://facebook.com/">Facebook</a></li></ul>'),'<svg'),'Ordinary list unchanged');
$out=$s->render('<ul class="chisimba-social-links" onclick="bad()"><li><a href="javascript:alert(1)">Bad</a><svg onload="bad()"></svg></li></ul>');
check(!str_contains($out,'javascript:')&&!str_contains($out,'onclick')&&!str_contains($out,'<svg'),'Unsafe authored content rejected');
foreach(['facebook','youtube','tiktok','flickr','pexels'] as $brand){$svg=$GLOBALS['services']['iconservice']->render('brand-'.$brand,['decorative'=>true]);check(str_contains($svg,'fill="currentColor"')&&str_contains($svg,'aria-hidden="true"')&&!str_contains($svg,'role="img"'),'Decorative local brand icon');}
echo "PASS: social links, exact hosts, distinct labels, local icons and sanitisation\n";
