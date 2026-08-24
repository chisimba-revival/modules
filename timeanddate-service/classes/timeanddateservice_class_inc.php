<?php
/** Canonical application-facing time and date service. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class timeanddateservice extends object
{
    private const CONFIG_MODULE = 'timeanddate-service';
    private const STORAGE_FORMAT = 'Y-m-d H:i:s';
    private const DEFAULT_TIMEZONE = 'UTC';
    private const DEFAULT_DATE_FORMAT = 'j F Y';
    private const DEFAULT_TIME_FORMAT = 'H:i';
    private const DEFAULT_DATETIME_FORMAT = 'j F Y, H:i';

    public $objConfig;

    public function init()
    {
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    public function nowUtc()
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function nowStorage()
    {
        return $this->toStorage($this->nowUtc());
    }

    public function parseStorage($value)
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        $parsed = DateTimeImmutable::createFromFormat(
            '!' . self::STORAGE_FORMAT,
            $value,
            new DateTimeZone('UTC')
        );
        return $this->isExactParse($parsed, self::STORAGE_FORMAT, $value)
            ? $parsed : null;
    }

    public function toStorage($value, $sourceTimezone = null)
    {
        $instant = $this->toUtc($value, $sourceTimezone);
        return $instant === null ? null : $instant->format(self::STORAGE_FORMAT);
    }

    /** Strictly parse a local wall-clock value and return its UTC instant. */
    public function parseLocal(
        $value,
        $timezone = null,
        $format = self::STORAGE_FORMAT
    ) {
        if (!is_string($value) || !is_string($format) || $format === '') {
            return null;
        }
        $value = trim($value);
        $zone = $this->timezone($timezone);
        if ($zone === null) {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value, $zone);
        if (!$this->isExactParse($parsed, $format, $value)) {
            return null;
        }
        return $parsed->setTimezone(new DateTimeZone('UTC'));
    }

    public function toUtc($value, $sourceTimezone = null)
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new DateTimeZone('UTC'));
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $stored = $this->parseStorage($value);
        if ($stored !== null && $sourceTimezone === null) {
            return $stored;
        }
        $zone = $this->timezone($sourceTimezone);
        if ($zone === null) {
            return null;
        }
        try {
            return (new DateTimeImmutable($value, $zone))
                ->setTimezone(new DateTimeZone('UTC'));
        } catch (Exception $exception) {
            return null;
        }
    }

    public function inTimezone($value, $timezone = null)
    {
        $instant = $this->toUtc($value);
        $zone = $this->timezone($timezone);
        return $instant === null || $zone === null
            ? null : $instant->setTimezone($zone);
    }

    public function formatDate($value, $timezone = null)
    {
        return $this->format($value, $timezone, 'TIMEANDDATE_DATE_FORMAT', self::DEFAULT_DATE_FORMAT);
    }

    public function formatTime($value, $timezone = null)
    {
        return $this->format($value, $timezone, 'TIMEANDDATE_TIME_FORMAT', self::DEFAULT_TIME_FORMAT);
    }

    public function formatDateTime($value, $timezone = null)
    {
        return $this->format($value, $timezone, 'TIMEANDDATE_DATETIME_FORMAT', self::DEFAULT_DATETIME_FORMAT);
    }

    public function siteTimezone()
    {
        $configured = trim((string) $this->objConfig->getValue(
            'TIMEANDDATE_TIMEZONE', self::CONFIG_MODULE, self::DEFAULT_TIMEZONE
        ));
        return $this->isValidTimezone($configured)
            ? $configured : self::DEFAULT_TIMEZONE;
    }

    public function isValidTimezone($timezone)
    {
        if (!is_string($timezone) || trim($timezone) === '') {
            return false;
        }
        $timezone = trim($timezone);
        return $timezone === 'UTC'
            || in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }

    private function timezone($timezone)
    {
        $name = $timezone === null ? $this->siteTimezone() : trim((string) $timezone);
        return $this->isValidTimezone($name) ? new DateTimeZone($name) : null;
    }

    private function format($value, $timezone, $configName, $default)
    {
        $local = $this->inTimezone($value, $timezone);
        if ($local === null) {
            return null;
        }
        $format = trim((string) $this->objConfig->getValue(
            $configName, self::CONFIG_MODULE, $default
        ));
        return $local->format($format === '' ? $default : $format);
    }

    private function isExactParse($parsed, $format, $value)
    {
        if (!$parsed instanceof DateTimeImmutable) {
            return false;
        }
        $errors = DateTimeImmutable::getLastErrors();
        if (is_array($errors)
            && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }
        return $parsed->format($format) === $value;
    }
}
?>
