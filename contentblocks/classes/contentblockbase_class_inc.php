<?php
/**
 * Content blocks module component.
 *
 * This file forms part of the Chisimba Content blocks module.
 *
 * PHP version 8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @category  Chisimba
 * @package   contentblocks
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/modules
 */
/**
 * Renders database-backed Hero, Video Hero and Information blocks.
 *
 * @category Chisimba
 * @package  contentblocks
 * @author   Derek Keats
 */
class contentblockbase extends ChisimbaObject
{
    private $db;
    private $washout;

    public function init()
    {
        $this->db = $this->getObject('dbcontentblocks', 'contentblocks');
        $this->washout = $this->getObject('washout', 'utilities');
        $href = htmlspecialchars($this->getResourceUri('contentblocks.css', 'contentblocks'), ENT_QUOTES, 'UTF-8');
        $this->appendArrayVar('headerParams', '<link rel="stylesheet" href="' . $href . '">');
    }

    private function currentContext()
    {
        try {
            return (string)$this->getObject('dbcontext', 'context')->getContextCode();
        } catch (Throwable $e) {
            return '';
        }
    }

    private function safeUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '' || str_starts_with($url, '/')) {
            return $url;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https'), true) ? $url : '';
    }

    /**
     * Convert recognised public video links to privacy-conscious player URLs.
     *
     * @param string $url Author-supplied video URL.
     *
     * @return string|null
     */
    private function recognisedVideoEmbedUrl($url)
    {
        $parts = parse_url($url);
        $host = strtolower(isset($parts['host']) ? $parts['host'] : '');
        $host = preg_replace('/^www\\./', '', $host);
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
        if (in_array($host, array('vimeo.com', 'player.vimeo.com'), true)
            && preg_match('#(?:video/)?([0-9]{6,12})#', $path, $match)) {
            return 'https://player.vimeo.com/video/' . $match[1];
        }
        return null;
    }

    public function setDataArr($blockKey)
    {
        $row = $this->db->findByKey($blockKey);
        if (!$row) {
            // Legacy page editors store the content row id, while newer
            // registrations store blockkey. Both identify the same block.
            $row = $this->db->find($blockKey);
        }
        if (!$row || (($row['scope'] ?? '') === 'context' && ($row['contextcode'] ?? '') !== $this->currentContext())) {
            return array('title' => $blockKey, 'blockContents' => '');
        }
        $title = htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8');
        $body = $this->washout->parseText((string)($row['body_html'] ?? ''));
        $image = $this->safeUrl($row['image_url'] ?? '');
        $actionUrl = $this->safeUrl($row['action_url'] ?? '');
        $actionLabel = htmlspecialchars((string)($row['action_label'] ?? ''), ENT_QUOTES, 'UTF-8');
        if (($row['blocktype'] ?? '') === 'videohero') {
            $embedUrl = $this->recognisedVideoEmbedUrl($image);
            if ($embedUrl !== null) {
                $html = '<div class="content-block content-block--video-hero"><iframe src="'
                    . htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8')
                    . '" title="' . $title
                    . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
            } else {
                $html = '<video class="content-block content-block--video-hero" controls playsinline preload="metadata" src="'
                    . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '"></video>';
            }
            return array(
                'title' => false,
                'blockContents' => $html,
                'blockType' => 'none',
                'cssClass' => '',
                'cssId' => '',
                'show_title' => 0,
            );
        }
        $kind = ($row['blocktype'] ?? '') === 'hero' ? 'hero' : 'information';
        $tag = $kind === 'hero' ? 'section' : 'article';
        $heading = $kind === 'hero' ? 'h1' : 'h2';
        $placement = ($row['blockwidth'] ?? 'wide') === 'normal' ? 'normal' : 'wide';
        $html = '<' . $tag . ' class="content-block content-block--' . $kind . ' content-block--' . $placement . '">';
        if ($kind === 'hero' && $image !== '') {
            $html .= '<img class="content-block__image" src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="">';
        }
        $html .= '<div class="content-block__inner">';
        if (($row['show_title'] ?? '1') === '1') {
            $html .= '<' . $heading . ' class="content-block__title">' . $title . '</' . $heading . '>';
        }
        $html .= '<div class="content-block__body">' . $body . '</div>';
        if ($kind === 'hero' && $actionUrl !== '' && $actionLabel !== '') {
            $html .= '<a class="content-block__action" href="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '">' . $actionLabel . '</a>';
        }
        $html .= '</div></' . $tag . '>';
        return array(
            'title' => false,
            'blockContents' => $html,
            'blockType' => 'none',
            'cssClass' => '',
            'cssId' => '',
            'show_title' => 0,
        );
    }
}
?>
