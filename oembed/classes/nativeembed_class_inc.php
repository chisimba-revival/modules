<?php
/** Native media previews. No provider requests, JSONP or local-file lookup. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class nativeembed extends ChisimbaObject
{
    private $loaded = [];
    public function init() {}

    /** Resolve a URL using shared media recognition; never fetch the URL. */
    public function describe($value)
    {
        if (!is_string($value) || strlen($value) > 4096) return null;
        $url = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('//u', $url) !== 1 || preg_match('/[\x00-\x20\x7f]/', $url)) return null;
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return null;
        // Bound parsing before invoking the shared recogniser's query parser.
        $query = $parts['query'] ?? '';
        $limit = min(64, max(1, (int) ini_get('max_input_vars')));
        if (substr_count($query, '&') + 1 > $limit) return null;
        foreach (explode('&', $query) as $pair) {
            $key = urldecode(explode('=', $pair, 2)[0]);
            if (strpbrk($key, '[]') !== false) return null;
        }
        $embed = $this->getObject('contentmediaservice', 'contentblocks')->videoEmbed($url);
        if ($embed !== null) return ['kind'=>'frame', 'url'=>$embed];
        // HTTP URLs remain ordinary links instead of creating mixed-content embeds.
        if (strtolower($parts['scheme']) !== 'https') return null;
        $extension = strtolower(pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION));
        $kind = match ($extension) {
            'jpg','jpeg','png','gif','webp','avif' => 'image',
            'mp3','ogg','wav','m4a' => 'audio',
            'mp4','webm','ogv' => 'video',
            default => null
        };
        return $kind === null ? null : ['kind'=>$kind, 'url'=>$url];
    }

    /** Load progressive enhancement once per explicit scope; preserve all links. */
    public function load($selector = 'a[data-native-embed]')
    {
        if (isset($this->loaded[$selector])) return;
        $this->loaded[$selector] = true;
        $e = static fn($v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lang = $this->getObject('language','language');
        $script = '<script defer src="'.$e($this->getResourceUri('nativeembed.js','oembed')).'"'
            .' data-embed-selector="'.$e($selector).'"'
            .' data-embed-endpoint="'.$e($this->uri(['action'=>'resolve'],'oembed')).'"'
            .' data-embed-label="'.$e($lang->languageText('mod_oembed_showmedia','oembed','Show media')).'"'
            .' data-embed-title="'.$e($lang->languageText('mod_oembed_preview','oembed','Media preview')).'"></script>';
        $this->appendArrayVar('headerParams', $script);
    }

    /** Explicit filter/demo link. Invalid schemes become harmless text. */
    public function link($value)
    {
        if (!is_string($value)) return '';
        $url = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $e = static fn($v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $parts = parse_url($url);
        if (strlen($url)>4096 || preg_match('/[\x00-\x20\x7f]/',$url) || !$parts
            || !in_array(strtolower($parts['scheme']??''),['http','https'],true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return $e($url);
        $this->load();
        return '<a data-native-embed href="'.$e($url).'">'.$e($url).'</a>';
    }
}
