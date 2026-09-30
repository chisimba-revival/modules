<?php
/** Shared recognised-video conversion, extracted from the existing block renderer. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class contentmediaservice extends ChisimbaObject
{
    public function init() {}
    public function videoEmbed($url)
    {
        $parts = parse_url($url);
        $host = strtolower(isset($parts['host']) ? $parts['host'] : '');
        $host = preg_replace('/^www\\./', '', $host);
        if(!in_array(strtolower($parts['scheme']??''),['http','https'],true)||isset($parts['user'])||isset($parts['pass']))return null;
        $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
        if ($host === 'youtu.be' && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $path)) {
            return 'https://www.youtube-nocookie.com/embed/' . $path;
        }
        if (in_array($host, array('youtube.com', 'm.youtube.com', 'youtube-nocookie.com'), true)) {
            parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
            $id = isset($query['v']) ? $query['v'] : (preg_match('#^(?:shorts|embed)/([A-Za-z0-9_-]{6,20})#', $path, $match) ? $match[1] : '');
            if (preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
                return 'https://www.youtube-nocookie.com/embed/' . $id;
            }
        }
        if ($host === 'tiktok.com' && preg_match('~^@[A-Za-z0-9_.]+/video/([0-9]{10,25})/?$~D',$path,$match)) return 'https://www.tiktok.com/player/v1/'.$match[1];
        if ($host === 'xeno-canto.org' && preg_match('~^([0-9]{1,12})(?:/embed)?$~D', $path, $match)) return 'https://xeno-canto.org/'.$match[1].'/embed';
        if (in_array($host, array('vimeo.com', 'player.vimeo.com'), true)
            && preg_match('#(?:video/)?([0-9]{6,12})#', $path, $match)) {
            return 'https://player.vimeo.com/video/' . $match[1];
        }
        return null;
    }
}
