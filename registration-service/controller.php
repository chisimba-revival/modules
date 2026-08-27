<?php
/** Public account registration, verification, and recovery web boundary. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class registration_service extends controller
{
    private const REGISTER_CSRF = 'registration_service_register';
    private const RECOVERY_REQUEST_CSRF = 'registration_service_recovery_request';
    private const RECOVERY_RESET_CSRF = 'registration_service_recovery_reset';
    private const VERIFICATION_RETRY_CSRF = 'registration_service_verification_retry';
    private const POLICY_KEY = 'account_terms';
    private const POLICY_VERSION = '1.1.0';

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
        $this->phones = $this->getObject('internationalphonenumber', 'registration-service');
    }

    public function requiresLogin($action) { return false; }

    public function isValid($action, $default = true)
    {
        return in_array((string) $action, array(
            '', 'default', 'register', 'verify', 'terms',
            'forgotpassword', 'requestrecovery', 'recover', 'resetpassword',
            'usernameavailability', 'checkemail'
            , 'deliverypending', 'retryverification'
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
            case 'usernameavailability': return $this->usernameAvailability();
            case 'checkemail': return $this->confirmationPage();
            case 'deliverypending': return $this->deliveryPendingPage();
            case 'retryverification': return $this->retryVerification();
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

    private function usernameAvailability()
    {
        $result = $this->service->usernameAvailability(
            $this->scalarParam('username'),
            $this->scalarParam('first_name'),
            $this->scalarParam('surname')
        );
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function registrationPage($errorCode = '', array $values = array())
    {
        $returnTo=$this->captureContinuation();
        $defaultCode=$this->phones->defaultCallingCode(
            $this->getObject('altconfig','config')->getValue(
                'DEFAULT_CALLING_CODE','registration-service'
            )
        );
        if(empty($values['countryCallingCode'])) $values['countryCallingCode']=$defaultCode;
        $this->setVar('registrationCsrf', $this->csrf->issue(self::REGISTER_CSRF));
        $this->setVar('registrationAbuse', $this->abuse->issueFormEvidence('registration.create'));
        $this->setVar('registrationError', $errorCode);
        $this->setVar('registrationValues', $values);
        $this->setVar('registrationCallingCodes', $this->phones->callingCodes());
        $this->setVar('registrationReturnTo', $returnTo);
        return 'register_tpl.php';
    }

    private function submitRegistration()
    {
        if (!$this->isPost()) { return $this->registrationPage('post_required'); }
        $returnTo=$this->captureContinuation();
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
            if (($pending['code'] ?? '') === 'registration_already_pending'
                && !empty($pending['pendingId'])) {
                $resumed = $this->service->resumeVerification(
                    $pending['pendingId'],
                    $this->registrationPolicy(),
                    $returnTo
                );
                if (!empty($resumed['ok'])) {
                    return $this->checkEmailPage($resumed, $values);
                }
            }
            $this->recordAbuse('registration.create', $values['emailAddress'], false);
            return $this->registrationPage((string) ($pending['code'] ?? 'registration_failed'), $values);
        }
        $queued = $this->service->acceptLegalAndQueueVerification(
            $pending['pendingId'],
            $this->registrationPolicy(),
            array(
                'acceptanceMethod' => 'checkbox',
                'ipAddress' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'userAgent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
                'locale' => 'en_ZA',
            ),
            $returnTo
        );
        if (empty($queued['ok'])) {
            if (($queued['code'] ?? '') === 'verification_email_failed'
                && !empty($queued['pendingId'])) {
                return $this->deliveryPendingRedirect($queued['pendingId'], $values, $returnTo);
            }
            return $this->registrationPage((string) ($queued['code'] ?? 'verification_email_failed'), $values);
        }
        $this->recordAbuse('registration.create', $values['emailAddress'], true);
        return $this->checkEmailPage($queued, $values);
    }

    private function registrationPolicy()
    {
        $content = $this->policyContent();
        return array(
            'policyKey' => self::POLICY_KEY,
            'policyVersion' => self::POLICY_VERSION,
            'contentDigest' => hash(
                'sha256',
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ),
        );
    }

    private function policyContent()
    {
        $text = function ($key) {
            return $this->objLanguage->languageText(
                'mod_registration_service_' . $key,
                'registration-service'
            );
        };
        $section = function ($prefix, $paragraphCount) use ($text) {
            $paragraphs = array();
            for ($number = 1; $number <= $paragraphCount; $number++) {
                $paragraphs[] = $text($prefix . '_p' . $number);
            }
            return array('heading' => $text($prefix . '_heading'), 'paragraphs' => $paragraphs);
        };

        return array(
            'terms' => array(
                'title' => $text('terms_of_use_title'),
                'introduction' => $text('terms_of_use_intro'),
                'sections' => array(
                    $section('terms_accounts', 2), $section('terms_learning', 2),
                    $section('terms_conduct', 2), $section('terms_content', 2),
                    $section('terms_payments', 2), $section('terms_availability', 2),
                    $section('terms_end', 2), $section('terms_changes', 2),
                ),
            ),
            'privacy' => array(
                'title' => $text('privacy_notice_title'),
                'introduction' => $text('privacy_notice_intro'),
                'sections' => array(
                    $section('privacy_collect', 2), $section('privacy_use', 2),
                    $section('privacy_share', 2), $section('privacy_payment', 2),
                    $section('privacy_retention', 2), $section('privacy_security', 2),
                    $section('privacy_rights', 2), $section('privacy_children', 1),
                    $section('privacy_changes', 2),
                ),
            ),
        );
    }

    private function checkEmailPage(array $result, array $fallback)
    {
        $this->setSession('registration_service_confirmation', array(
            'emailAddress' => $result['emailAddress'] ?? $fallback['emailAddress'],
            'username' => $result['username'] ?? $fallback['username'],
        ));
        $location = html_entity_decode(
            $this->uri(array('action' => 'checkemail'), 'registration-service'),
            ENT_QUOTES,
            'UTF-8'
        );
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function confirmationPage()
    {
        $confirmation = $this->getSession('registration_service_confirmation');
        if (!is_array($confirmation) || empty($confirmation['emailAddress'])) {
            return $this->registrationPage();
        }
        $this->setVar('registrationEmail', $confirmation['emailAddress']);
        $this->setVar('registrationUsername', $confirmation['username'] ?? '');
        return 'check_email_tpl.php';
    }

    private function deliveryPendingRedirect($pendingId, array $values, $returnTo = '')
    {
        $this->setSession('registration_service_delivery_retry', array(
            'pendingId' => (string) $pendingId,
            'emailAddress' => $values['emailAddress'],
            'username' => $values['username'],
            'returnTo' => $returnTo,
        ));
        $location = html_entity_decode(
            $this->uri(array('action' => 'deliverypending'), 'registration-service'),
            ENT_QUOTES,
            'UTF-8'
        );
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function deliveryPendingPage($errorCode = '')
    {
        $retry = $this->getSession('registration_service_delivery_retry');
        if (!is_array($retry) || empty($retry['pendingId'])) {
            return $this->registrationPage();
        }
        $this->setVar('deliveryRetryCsrf', $this->csrf->issue(self::VERIFICATION_RETRY_CSRF));
        $this->setVar('deliveryRetryEmail', $retry['emailAddress'] ?? '');
        $this->setVar('deliveryRetryUsername', $retry['username'] ?? '');
        $this->setVar('deliveryRetryError', $errorCode);
        return 'delivery_pending_tpl.php';
    }

    private function retryVerification()
    {
        if (!$this->isPost()
            || !$this->csrf->consume(self::VERIFICATION_RETRY_CSRF, $this->scalarParam('csrf_token'))) {
            return $this->deliveryPendingPage('invalid_request');
        }
        $retry = $this->getSession('registration_service_delivery_retry');
        if (!is_array($retry) || empty($retry['pendingId'])) {
            return $this->registrationPage();
        }
        $result = $this->service->resumeVerification(
            $retry['pendingId'],
            $this->registrationPolicy(),
            $this->validatedContinuation($retry['returnTo']??'')??''
        );
        if (empty($result['ok'])) {
            return $this->deliveryPendingPage((string) ($result['code'] ?? 'verification_email_failed'));
        }
        $this->unsetSession('registration_service_delivery_retry');
        return $this->checkEmailPage($result, array(
            'emailAddress' => $retry['emailAddress'] ?? '',
            'username' => $retry['username'] ?? '',
        ));
    }

    private function verifyAccount()
    {
        $returnTo=$this->captureContinuation();
        $this->unsetSession('registration_service_return_to');
        $verified = $this->service->completeEmailVerification($this->scalarParam('token'));
        if (empty($verified['ok']) || empty($verified['pendingId'])) {
            $this->setVar('verificationResult', array('ok' => false, 'code' => $verified['code'] ?? 'verification_failed'));
            return 'verification_result_tpl.php';
        }
        $result = $this->service->provisionVerified($verified['pendingId']);
        $this->setVar('verificationResult', $result);
        $this->setVar('registrationReturnTo', $returnTo);
        return 'verification_result_tpl.php';
    }

    /** Preserve only a local, non-authentication destination through signup. */
    private function captureContinuation()
    {
        $candidate=$this->validatedContinuation($this->scalarParam('return_to'));
        if($candidate!==null){$this->setSession('registration_service_return_to',$candidate);return $candidate;}
        $candidate=$this->validatedContinuation($this->getSession('registration_service_return_to',''));
        return $candidate===null?'':$candidate;
    }

    private function validatedContinuation($candidate)
    {
        if(is_array($candidate)||is_object($candidate))return null;
        $candidate=html_entity_decode(trim((string)$candidate),ENT_QUOTES,'UTF-8');
        if($candidate===''||strlen($candidate)>2048||preg_match('/[\x00-\x1F\x7F\\\\]/',$candidate)||strncmp($candidate,'//',2)===0)return null;
        $parts=parse_url($candidate);
        if($parts===false||isset($parts['scheme'])||isset($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])||isset($parts['fragment']))return null;
        $path=(string)($parts['path']??'');$script=(string)($_SERVER['SCRIPT_NAME']??'/index.php');$base=rtrim(str_replace('\\','/',dirname($script)),'/');$prefix=$base===''?'/':$base.'/';
        if($path!==$base&&strncmp($path,$prefix,strlen($prefix))!==0)return null;
        parse_str((string)($parts['query']??''),$query);
        if(($query['module']??'')==='security')return null;
        return $candidate;
    }

    private function termsPage()
    {
        $this->setVar('registrationPolicyContent', $this->policyContent());
        $this->setVar('registrationPolicyVersion', self::POLICY_VERSION);
        $this->setVar('registrationPolicyPage', true);
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
            'countryCallingCode' => trim($this->scalarParam('country_calling_code')),
            'mobileNumber' => trim($this->scalarParam('mobile_number')),
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
