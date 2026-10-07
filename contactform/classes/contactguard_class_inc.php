<?php
/** Contact policy built on the canonical first-party abuse service. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class contactguard extends ChisimbaObject
{
    private function abuse(){return $this->getObject('nativeauthwebcomposition','security')->build()['abuse'];}
    public function evidence(){return $this->abuse()->issueFormEvidence('contact.submit');}
    public function check(array $input)
    {
        $this->loadClass('trustedclientaddress','abuseprotection');
        $config=$this->getObject('dbsysconfig','sysconfig');
        $trusted=$config->getValue('CONTACT_TRUSTED_PROXIES','contactform','');
        $context=['ip'=>TrustedClientAddress::resolve($_SERVER,$trusted),'account'=>strtolower(trim($input['email']??'')),'session'=>session_id()];
        $evidence=['website'=>$input['website']??'','issued_at'=>$input['abuse_issued_at']??'', 'nonce'=>$input['abuse_nonce']??'','signature'=>$input['abuse_signature']??''];
        $abuse=$this->abuse();
        $decision=$abuse->evaluate('contact.submit',$context,$evidence,['minimum_seconds'=>2,'maximum_seconds'=>604800]);
        if(!$decision->isAllowed()){$abuse->record('contact.submit',$context,false);throw new DomainException('protection');}
        $limits=[];
        foreach([['ip',3600,'CONTACT_IP_HOURLY',10],['ip',86400,'CONTACT_IP_DAILY',50],['account',3600,'CONTACT_SENDER_HOURLY',3],['site',3600,'CONTACT_SITE_HOURLY',100],['site',86400,'CONTACT_SITE_DAILY',300]] as [$dimension,$seconds,$key,$default]){
            $count=filter_var($config->getValue($key,'contactform',$default),FILTER_VALIDATE_INT);
            $limits[]=['dimension'=>$dimension,'seconds'=>$seconds,'count'=>$count!==false&&$count>=1&&$count<=10000?$count:$default];
        }
        if(!$abuse->admit('contact.submit',$context,$limits)->isAllowed())throw new DomainException('limited');
        return $context['ip'];
    }
}
