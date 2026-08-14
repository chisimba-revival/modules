<?php
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

    public function setDataArr($blockKey)
    {
        $row = $this->db->findByKey($blockKey);
        if (!$row || (($row['scope'] ?? '') === 'context' && ($row['contextcode'] ?? '') !== $this->currentContext())) {
            return array('title' => $blockKey, 'blockContents' => '');
        }
        $title = htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8');
        $body = $this->washout->parseText((string)($row['body_html'] ?? ''));
        $image = $this->safeUrl($row['image_url'] ?? '');
        $actionUrl = $this->safeUrl($row['action_url'] ?? '');
        $actionLabel = htmlspecialchars((string)($row['action_label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $kind = ($row['blocktype'] ?? '') === 'hero' ? 'hero' : 'information';
        $tag = $kind === 'hero' ? 'section' : 'article';
        $heading = $kind === 'hero' ? 'h1' : 'h2';
        $html = '<' . $tag . ' class="content-block content-block--' . $kind . '">';
        if ($image !== '') {
            $html .= '<img class="content-block__image" src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="">';
        }
        $html .= '<div class="content-block__inner">';
        if (($row['show_title'] ?? '1') === '1') {
            $html .= '<' . $heading . ' class="content-block__title">' . $title . '</' . $heading . '>';
        }
        $html .= '<div class="content-block__body">' . $body . '</div>';
        if ($actionUrl !== '' && $actionLabel !== '') {
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
