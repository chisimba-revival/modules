<?php
/** Public account registration, verification, and recovery web boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class registration_service extends controller
{
    private const REGISTER_CSRF = 'registration_service_register';
    private const RECOVERY_REQUEST_CSRF = 'registration_service_recovery_request';
    private const RECOVERY_RESET_CSRF = 'registration_service_recovery_reset';
    private const POLICY_KEY = 'account_terms';
    private const POLICY_VERSION = '1.0.0';
    private const POLICY_TEXT = 'I agree to the Terms of Use and acknowledge the Privacy Notice.';

    private $service;
    private $csrf;
    private $abuse;
    public $objLanguage;

    public function init()
    {
        $this->service = $this->getObject('registrationservice', 'registration-service');
        $this->objLanguage = $this->getObject('language', 'language');
        $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
        $this->csrf = $stack['csrf'];
        $this->abuse = $stack['abuse'];
    }

    public function requiresLogin($action) { return false; }

    public function isValid($action, $default = true)
    {
        return in_array((string) $action, array(
            '', 'default', 'register', 'verify', 'terms',
            'forgotpassword', 'requestrecovery', 'recover', 'resetpassword'
        ), true);
    }

    public function dispatch($action)
    {
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        $this->setLayoutTemplate('registration_layout_tpl.php');
        $this->setVar('pageSuppressToolbar', true);
        $guidancePrefix = in_array((string) $action, array(
            'recover', 'resetpassword'
        ), true) ? 'recovery_reset_guidance' : (in_array((string) $action, array(
            'forgotpassword', 'requestrecovery'
        ), true) ? 'recovery_guidance' : 'guidance');
        $this->setVar('registrationGuidancePrefix', $guidancePrefix);
        switch ((string) $action) {
            case 'register': return $this->submitRegistration();
            case 'verify': return $this->verifyAccount();
            case 'terms': return $this->termsPage();
            case 'forgotpassword': return $this->recoveryRequestPage();
            case 'requestrecovery': return $this->requestRecovery();
            case 'recover': return $this->recoveryResetPage();
            case 'resetpassword': return $this->resetPassword();
            case '':
            case 'default':
            default: return $this->registrationPage();
        }
    }

    private function registrationPage($errorCode = '', array $values = array())
    {
        $this->setVar('registrationCsrf', $this->csrf->issue(self::REGISTER_CSRF));
        $this->setVar('registrationAbuse', $this->abuse->issueFormEvidence('registration.create'));
        $this->setVar('registrationError', $errorCode);
        $this->setVar('registrationValues', $values);
        return 'register_tpl.php';
    }

    private function submitRegistration()
    {
        if (!$this->isPost()) { return $this->registrationPage('post_required'); }
        $values = $this->registrationValues();
        if (!$this->csrf->consume(self::REGISTER_CSRF, $this->scalarParam('csrf_token'))
            || !$this->abuseAllowed('registration.create', $values['emailAddress'])) {
            return $this->registrationPage('invalid_request', $values);
        }
        if ($this->scalarParam('accept_terms') !== '1') {
            return $this->registrationPage('legal_required', $values);
        }
        $password = $this->scalarParam('password');
        if ($password === '' || !hash_equals($password, $this->scalarParam('password_confirm'))) {
            return $this->registrationPage('password_mismatch', $values);
        }
        if (strlen($password) < 12) {
            return $this->registrationPage('weak_password', $values);
        }
        $correlation = 'registration.web.' . bin2hex(random_bytes(16));
        $pending = $this->service->createPending($values + array(
            'password' => $password,
            'correlationId' => $correlation,
        ));
        if (empty($pending['ok'])) {
            $this->recordAbuse('registration.create', $values['emailAddress'], false);
            return $this->registrationPage((string) ($pending['code'] ?? 'registration_failed'), $values);
        }
        $queued = $this->service->acceptLegalAndQueueVerification(
            $pending['pendingId'],
            array(
                'policyKey' => self::POLICY_KEY,
                'policyVersion' => self::POLICY_VERSION,
                'contentDigest' => hash('sha256', self::POLICY_TEXT),
            ),
            array(
                'acceptanceMethod' => 'checkbox',
                'ipAddress' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'userAgent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
                'locale' => 'en_ZA',
            )
        );
        if (empty($queued['ok'])) {
            return $this->registrationPage((string) ($queued['code'] ?? 'verification_email_failed'), $values);
        }
        $this->recordAbuse('registration.create', $values['emailAddress'], true);
        $this->setVar('registrationEmail', $values['emailAddress']);
        return 'check_email_tpl.php';
    }

    private function verifyAccount()
    {
        $verified = $this->service->completeEmailVerification($this->scalarParam('token'));
        if (empty($verified['ok']) || empty($verified['pendingId'])) {
            $this->setVar('verificationResult', array('ok' => false, 'code' => $verified['code'] ?? 'verification_failed'));
            return 'verification_result_tpl.php';
        }
        $result = $this->service->provisionVerified($verified['pendingId']);
        $this->setVar('verificationResult', $result);
        return 'verification_result_tpl.php';
    }

    private function termsPage()
    {
        $this->setVar('registrationPolicyText', self::POLICY_TEXT);
        $this->setVar('registrationPolicyVersion', self::POLICY_VERSION);
        return 'terms_tpl.php';
    }

    private function recoveryRequestPage($errorCode = '')
    {
        $this->setVar('recoveryRequestCsrf', $this->csrf->issue(self::RECOVERY_REQUEST_CSRF));
        $this->setVar('recoveryAbuse', $this->abuse->issueFormEvidence('registration.recovery'));
        $this->setVar('recoveryError', $errorCode);
        return 'recovery_request_tpl.php';
    }

    private function requestRecovery()
    {
        if (!$this->isPost()) { return $this->recoveryRequestPage('post_required'); }
        $email = strtolower(trim($this->scalarParam('email_address')));
        if (!$this->csrf->consume(self::RECOVERY_REQUEST_CSRF, $this->scalarParam('csrf_token'))
            || !$this->abuseAllowed('registration.recovery', $email)) {
            return $this->recoveryRequestPage('invalid_request');
        }
        $result = $this->service->requestPasswordRecovery(
            $email,
            'recovery.web.' . bin2hex(random_bytes(16))
        );
        $this->recordAbuse('registration.recovery', $email, !empty($result['ok']));
        $this->setVar('recoveryRequested', true);
        return 'recovery_request_tpl.php';
    }

    private function recoveryResetPage($errorCode = '', $token = '')
    {
        $token = $token !== '' ? $token : $this->scalarParam('token');
        $this->setVar('recoveryResetCsrf', $this->csrf->issue(self::RECOVERY_RESET_CSRF));
        $this->setVar('recoveryToken', $token);
        $this->setVar('recoveryResetError', $errorCode);
        return 'recovery_reset_tpl.php';
    }

    private function resetPassword()
    {
        $token = $this->scalarParam('token');
        if (!$this->isPost()
            || !$this->csrf->consume(self::RECOVERY_RESET_CSRF, $this->scalarParam('csrf_token'))) {
            return $this->recoveryResetPage('invalid_request', $token);
        }
        $password = $this->scalarParam('password');
        if ($password === '' || !hash_equals($password, $this->scalarParam('password_confirm'))) {
            return $this->recoveryResetPage('password_mismatch', $token);
        }
        if (strlen($password) < 12) {
            return $this->recoveryResetPage('weak_password', $token);
        }
        $result = $this->service->completePasswordRecovery(
            $token,
            $password,
            'recovery.web.complete.' . bin2hex(random_bytes(12))
        );
        $this->setVar('recoveryCompletion', $result);
        return 'recovery_result_tpl.php';
    }

    private function registrationValues()
    {
        return array(
            'username' => strtolower(trim($this->scalarParam('username'))),
            'emailAddress' => strtolower(trim($this->scalarParam('email_address'))),
            'firstName' => trim($this->scalarParam('first_name')),
            'surname' => trim($this->scalarParam('surname')),
        );
    }

    private function abuseAllowed($action, $account)
    {
        $decision = $this->abuse->evaluate($action, $this->abuseContext($account), array(
            'issued_at' => $this->scalarParam('abuse_issued_at'),
            'nonce' => $this->scalarParam('abuse_nonce'),
            'signature' => $this->scalarParam('abuse_signature'),
            'website' => $this->scalarParam('website'),
        ), array('minimum_seconds' => 1, 'maximum_seconds' => 3600, 'failure_limit' => 5));
        return is_object($decision) && method_exists($decision, 'isAllowed') && $decision->isAllowed();
    }

    private function recordAbuse($action, $account, $success)
    {
        $this->abuse->record($action, $this->abuseContext($account), (bool) $success);
    }

    private function abuseContext($account)
    {
        return array(
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'account' => (string) $account,
            'session' => session_id(),
        );
    }

    private function scalarParam($name)
    {
        $value = $this->getParam($name, '');
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function isPost()
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST';
    }
}
class_alias('registration_service', 'registration-service');
?>
