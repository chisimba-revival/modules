<?php
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Discovers assessment providers declared by installed modules.
 *
 * A provider owns its activity records and raw results. Gradebook owns the
 * course assessment plan, its dates, weighting and contribution calculation.
 */
class assessmentproviderregistry extends ChisimbaObject
{
    private $objModules;
    private $objModuleFile;
    private $objLanguage;

    public function init()
    {
        $this->objModules = $this->getObject('modules', 'modulecatalogue');
        $this->objModuleFile = $this->getObject('modulefile', 'modulecatalogue');
        $this->objLanguage = $this->getObject('language', 'language');
    }

    /**
     * Return declared providers from modules installed in this Chisimba site.
     * Unavailable or incomplete declarations are ignored; a broken optional
     * module must not prevent the course assessment plan from opening.
     */
    public function all()
    {
        $providers = array();
        $modules = $this->objModules->getAll('ORDER BY module_id');
        foreach ((array) $modules as $module) {
            $moduleId = isset($module['module_id']) ? $module['module_id'] : '';
            if ($moduleId === '') {
                continue;
            }
            $registerFile = $this->objModuleFile->findregisterfile($moduleId);
            if (!$registerFile) {
                continue;
            }
            $definition = $this->objModuleFile->readRegisterFile($registerFile);
            if (!is_array($definition) || empty($definition['ASSESSMENT_PROVIDER'])) {
                continue;
            }
            $provider = $this->normalise($moduleId, $definition);
            if ($provider !== false) {
                $providers[$provider['key']] = $provider;
            }
        }
        uasort($providers, array($this, 'compareProviders'));
        return array_values($providers);
    }

    public function get($key)
    {
        foreach ($this->all() as $provider) {
            if ($provider['key'] === $key) {
                return $provider;
            }
        }
        return false;
    }

    /**
     * Load the provider-owned adapter declared in the manifest.  Gradebook
     * uses this only to browse and validate activities; it never writes to a
     * provider's activity or result tables.
     */
    public function adapter($key)
    {
        $provider = $this->get($key);
        if ($provider === false
            || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $provider['adapter_class'])) {
            return false;
        }
        return $this->getObject($provider['adapter_class'], $provider['module_id']);
    }

    private function normalise($moduleId, array $definition)
    {
        $key = isset($definition['ASSESSMENT_PROVIDER']) ? trim($definition['ASSESSMENT_PROVIDER']) : '';
        $labelKey = isset($definition['ASSESSMENT_PROVIDER_LABEL']) ? trim($definition['ASSESSMENT_PROVIDER_LABEL']) : '';
        $categoryKey = isset($definition['ASSESSMENT_PROVIDER_CATEGORY']) ? trim($definition['ASSESSMENT_PROVIDER_CATEGORY']) : '';
        $descriptionKey = isset($definition['ASSESSMENT_PROVIDER_DESCRIPTION']) ? trim($definition['ASSESSMENT_PROVIDER_DESCRIPTION']) : '';
        $adapterClass = isset($definition['ASSESSMENT_PROVIDER_CLASS']) ? trim($definition['ASSESSMENT_PROVIDER_CLASS']) : '';
        $icon = isset($definition['ASSESSMENT_PROVIDER_ICON'])
            ? trim($definition['ASSESSMENT_PROVIDER_ICON']) : 'clipboard-check';

        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $key)
            || $labelKey === '' || $categoryKey === '' || $descriptionKey === '' || $adapterClass === '') {
            return false;
        }

        $capabilities = array();
        if (!empty($definition['ASSESSMENT_PROVIDER_CAPABILITIES'])) {
            foreach (explode(',', $definition['ASSESSMENT_PROVIDER_CAPABILITIES']) as $capability) {
                $capability = trim($capability);
                if (preg_match('/^[a-z][a-z0-9_]{1,63}$/', $capability)) {
                    $capabilities[] = $capability;
                }
            }
        }

        return array(
            'key' => $key,
            'module_id' => $moduleId,
            'adapter_class' => $adapterClass,
            'capabilities' => $capabilities,
            'label_key' => $labelKey,
            'category_key' => $categoryKey,
            'description_key' => $descriptionKey,
            'label' => $this->objLanguage->languageText($labelKey, $moduleId),
            'category' => $this->objLanguage->languageText($categoryKey, $moduleId),
            'description' => $this->objLanguage->languageText($descriptionKey, $moduleId)
            ,'icon' => preg_match('/^[a-z0-9-]{1,64}$/', $icon) ? $icon : 'clipboard-check'
        );
    }

    private function compareProviders($left, $right)
    {
        $category = strcmp($left['category'], $right['category']);
        return $category === 0 ? strcmp($left['label'], $right['label']) : $category;
    }
}
?>
