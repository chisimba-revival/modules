<?php
/**
 * Scope resolution for Active Knowledge Maps.
 *
 * @author Derek Keats
 * @package knowledgemap
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Resolves personal, course and site scope without hiding it in controller state. */
class knowledgemapscopeservice extends controller
{
    private $user;
    private $context;

    /** Initialise scope dependencies. */
    public function init(){$this->user=$this->getObject('user','security');$this->context=$this->getObject('dbcontext','context');}

    /** Resolve an explicit requested scope against the current Chisimba context. */
    public function resolve($requested=''){$contextCode=(string)$this->context->getContextCode();if($requested==='site')return array('type'=>'site','id'=>'root','label'=>'Site maps');if($requested==='context'||($requested===''&&$contextCode!==''&&$contextCode!=='root'))return array('type'=>'context','id'=>$contextCode,'label'=>'Course maps');return array('type'=>'personal','id'=>(string)$this->user->userId(),'label'=>'My maps');}

    /** Return whether a map belongs to the supplied scope. */
    public function contains(array $map,array $scope){return ($map['scopetype']??'')===$scope['type']&&(string)($map['scopeid']??'')===(string)$scope['id'];}
}
?>
