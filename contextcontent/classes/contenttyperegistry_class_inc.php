<?php
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class contenttyperegistry extends ChisimbaObject
{
    private $types = array();

    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->register(array(
            'key' => 'rich_text',
            'icon' => 'file-text',
            'label' => $this->language->languageText('mod_contextcontent_type_richtext', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_richtext_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'standard'
        ));
        $this->register(array(
            'key' => 'short_text',
            'icon' => 'smartphone',
            'label' => $this->language->languageText('mod_contextcontent_type_shorttext', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_shorttext_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'microlearning'
        ));
        $this->register(array(
            'key' => 'image_audio',
            'icon' => 'image-plus',
            'label' => $this->language->languageText('mod_contextcontent_type_imageaudio', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_imageaudio_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'microlearning'
        ));
        $this->register(array(
            'key' => 'tiktok_video',
            'icon' => 'smartphone',
            'label' => $this->language->languageText('mod_contextcontent_type_tiktok_video', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_tiktok_video_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'microlearning'
        ));
        $this->register(array(
            'key' => 'video',
            'icon' => 'video',
            'label' => $this->language->languageText('mod_contextcontent_type_video', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_video_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'microlearning'
        ));
        $this->register(array(
            'key' => 'pdf',
            'icon' => 'file-down',
            'label' => $this->language->languageText('mod_contextcontent_type_pdf', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_pdf_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'all'
        ));
        $this->register(array(
            'key' => 'zip_bundle',
            'icon' => 'file-archive',
            'label' => $this->language->languageText('mod_contextcontent_type_zip_bundle', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_zip_bundle_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'all'
        ));
        $this->register(array(
            'key' => 'external_reading',
            'icon' => 'external-link',
            'label' => $this->language->languageText('mod_contextcontent_type_external_reading', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_external_reading_desc', 'contextcontent'),
            'native' => true,
            'preferred_for' => 'all'
        ));
        $this->register(array(
            'key' => 'assessment_activity',
            'icon' => 'clipboard-check',
            'label' => $this->language->languageText('mod_contextcontent_type_assessment', 'contextcontent'),
            'description' => $this->language->languageText('mod_contextcontent_type_assessment_desc', 'contextcontent'),
            'native' => false,
            'palette' => 'assessment',
            'preferred_for' => 'all'
        ));
    }

    public function register(array $definition)
    {
        $key = isset($definition['key']) ? (string) $definition['key'] : '';
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $key)) {
            throw new InvalidArgumentException('Invalid content type key');
        }
        if (isset($this->types[$key])) {
            throw new LogicException('Duplicate content type: ' . $key);
        }
        $this->types[$key] = $definition;
    }

    public function get($key)
    {
        if (!isset($this->types[$key])) {
            throw new InvalidArgumentException('Unsupported content type');
        }
        return $this->types[$key];
    }

    public function iconName($key, $fallback = 'file-text')
    {
        return isset($this->types[$key]['icon']) ? $this->types[$key]['icon'] : $fallback;
    }

    public function all($deliveryFormat = 'standard')
    {
        $types = array_values($this->types);
        usort($types, function ($left, $right) use ($deliveryFormat) {
            $a = ($left['preferred_for'] === $deliveryFormat) ? 0 : 1;
            $b = ($right['preferred_for'] === $deliveryFormat) ? 0 : 1;
            return $a === $b ? strcmp($left['label'], $right['label']) : $a - $b;
        });
        return $types;
    }

    public function forPalette($palette, $deliveryFormat = 'standard')
    {
        return array_values(array_filter(
            $this->all($deliveryFormat),
            static function ($type) use ($palette) {
                $typePalette = isset($type['palette']) ? $type['palette'] : 'content';
                return $typePalette === $palette;
            }
        ));
    }
}
?>
