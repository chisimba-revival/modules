<?php
/** First-party registration policy; external bot services are not required. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
class registrationguard extends ChisimbaObject
{
    public function number($key, $default, $min = 1, $max = 10000)
    {
        $value = $this->getObject('dbsysconfig', 'sysconfig')->getValue($key, 'registration-service', $default);
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value === false || $value < $min || $value > $max ? $default : $value;
    }
    /** Existing installations may still store seven days; confirmation is capped at 24 hours. */
    public function pendingDays() { return $this->number('REGISTRATION_PENDING_DAYS', 1, 1, 1); }
    public function retentionDays() { return $this->number('REGISTRATION_RETENTION_DAYS', 30, 1, 365); }
    public function automatic() { return $this->number('REGISTRATION_AUTO_CLEANUP', 0, 0, 1) === 1; }
    public function context($account = '')
    {
        $this->loadClass('trustedclientaddress', 'abuseprotection');
        $trusted = $this->getObject('dbsysconfig', 'sysconfig')->getValue('REGISTRATION_TRUSTED_PROXIES', 'registration-service', '');
        return array('ip' => TrustedClientAddress::resolve($_SERVER, $trusted),
            'account' => strtolower(trim((string) $account)), 'session' => session_id());
    }
    public function admit($action, $account = '')
    {
        $limits = array(
            array('dimension'=>'ip','seconds'=>3600,'count'=>$this->number('REGISTRATION_IP_HOURLY',20)),
            array('dimension'=>'ip','seconds'=>86400,'count'=>$this->number('REGISTRATION_IP_DAILY',100)),
            array('dimension'=>'account','seconds'=>3600,'count'=>3),
            array('dimension'=>'site','seconds'=>3600,'count'=>$this->number('REGISTRATION_SITE_HOURLY',100)),
            array('dimension'=>'site','seconds'=>86400,'count'=>$this->number('REGISTRATION_SITE_DAILY',500)),
        );
        return $this->getObject('nativeauthwebcomposition', 'security')->build()['abuse']
            ->admit($action, $this->context($account), $limits)->isAllowed();
    }
    /** Applies to every verification send path, including resends and reminders. */
    public function allowVerificationMail($account)
    {
        return $this->getObject('nativeauthwebcomposition', 'security')->build()['abuse']
            ->admit('registration.mail', $this->context($account), array(
                array('dimension'=>'account','seconds'=>3600,'count'=>3),
                array('dimension'=>'account','seconds'=>86400,'count'=>10),
                array('dimension'=>'site','seconds'=>3600,'count'=>$this->number('REGISTRATION_SITE_HOURLY',100)),
                array('dimension'=>'site','seconds'=>86400,'count'=>$this->number('REGISTRATION_SITE_DAILY',500)),
            ))->isAllowed();
    }
    /** A narrow review hint, never an account deletion or access decision. */
    public function suspiciousName($first, $surname = '')
    {
        $this->loadClass('submissioncontentpolicy', 'abuseprotection');
        return SubmissionContentPolicy::suspiciousName($first . ' ' . $surname);
    }
}
