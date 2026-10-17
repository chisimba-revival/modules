<?php
// Test only the compatibility declaration; never initialise the deferred importer.
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
class StopBeforeLegacyForm extends RuntimeException {}
class ImportTemplateProbe {
    public array $vars=[];
    public function getVar($key){return $this->vars[$key]??null;}
    public function setVar($key,$value){$this->vars[$key]=$value;}
    public function loadClass($name,$module){throw new StopBeforeLegacyForm();}
    public function render(){include dirname(__DIR__).'/templates/content/upload_eportfolio_tpl.php';}
}
foreach([null,true,false] as $initial){
    $probe=new ImportTemplateProbe();
    if($initial!==null)$probe->vars['SUPPRESS_JQUERY']=$initial;
    try{$probe->render();throw new RuntimeException('Probe did not reach form');}
    catch(StopBeforeLegacyForm $expected){}
    if($probe->vars['SUPPRESS_JQUERY']!==($initial??false))throw new RuntimeException('Compatibility precedence failed');
}
echo "PASS import form opts in only when no explicit suppression exists\n";
