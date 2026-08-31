<?php
/**
 * Push/pull provider discovery for Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Discovers provider-owned adapters declared by installed modules. */
class knowledgemapproviderregistry extends ChisimbaObject
{
    private $modules;
    private $moduleFile;
    private $language;

    /** Initialise module-catalogue dependencies. */
    public function init(){$this->modules=$this->getObject('modules','modulecatalogue');$this->moduleFile=$this->getObject('modulefile','modulecatalogue');$this->language=$this->getObject('language','language');}

    /** Return valid push and pull providers without allowing one broken provider to block the map. */
    public function all(){
        $providers=array();foreach((array)$this->modules->getAll('ORDER BY module_id') as $module){$moduleId=$module['module_id']??'';if($moduleId==='')continue;$registerFile=$this->moduleFile->findregisterfile($moduleId);if(!$registerFile)continue;$definition=$this->moduleFile->readRegisterFile($registerFile);if(!is_array($definition)||empty($definition['KNOWLEDGE_MAP_PROVIDER']))continue;$provider=$this->normalise($moduleId,$definition);if($provider)$providers[$provider['key']]=$provider;}uasort($providers,fn($left,$right)=>strcmp($left['label'],$right['label']));return array_values($providers);
    }

    /** Load a provider-owned adapter through the Chisimba object system. */
    public function adapter($key){foreach($this->all() as $provider)if($provider['key']===$key)return $this->getObject($provider['adapterClass'],$provider['moduleId']);return false;}

    /** Validate a provider manifest declaration. */
    private function normalise($moduleId,array $definition){$key=trim((string)($definition['KNOWLEDGE_MAP_PROVIDER']??''));$class=trim((string)($definition['KNOWLEDGE_MAP_PROVIDER_CLASS']??''));$direction=strtolower(trim((string)($definition['KNOWLEDGE_MAP_PROVIDER_DIRECTION']??'')));$labelKey=trim((string)($definition['KNOWLEDGE_MAP_PROVIDER_LABEL']??''));if(!preg_match('/^[a-z][a-z0-9_]{1,63}$/',$key)||!preg_match('/^[a-z][a-z0-9_]{1,63}$/',$class)||!in_array($direction,array('push','pull','both'),true)||$labelKey==='')return false;$scopes=array_values(array_intersect(array('node','descendants','whole_map'),array_map('trim',explode(',',(string)($definition['KNOWLEDGE_MAP_PROVIDER_SCOPES']??'')))));if(!$scopes)return false;return array('key'=>$key,'moduleId'=>$moduleId,'adapterClass'=>$class,'direction'=>$direction,'scopes'=>$scopes,'label'=>$this->language->languageText($labelKey,$moduleId));}
}
?>
