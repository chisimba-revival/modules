<?php
/** CSRF boundary shared by legacy author forms and their recovery screen. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class authoringrequest extends ChisimbaObject {
    private function namespace() { return 'mcq_author_'.hash('sha256',(string)$this->getObject('dbcontext','context')->getContextCode()); }
    public function input() {
        $token=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue($this->namespace());
        return '<input type="hidden" name="author_token" value="'.htmlspecialchars($token,ENT_QUOTES,'UTF-8').'">';
    }
    public function valid($token) {
        return strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='POST'
            && $this->getObject('nativeauthwebcomposition','security')->build()['csrf']->consume($this->namespace(),(string)$token);
    }
    /** Preserve scalar form data only. Never replay uploaded files automatically. */
    public function draft(array $input) {
        $result=array();$size=0;
        foreach($input as $key=>$value) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,80}$/D',(string)$key) || !is_scalar($value)
                || in_array($key,array('module','action','author_token','csrf_token'),true)) continue;
            $value=mb_substr((string)$value,0,100000);$size+=strlen($value);
            if ($size>1000000 || count($result)>=300) break;
            $result[$key]=$value;
        }
        return $result;
    }
}
