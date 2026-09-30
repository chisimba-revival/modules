<?php
/** Reader projection of a publication; never sends hidden content to the browser. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class accesspreview extends ChisimbaObject
{
    public function init() {}

    /**
     * Project every body representation before passing a post to summaries or
     * metadata. Replacing post_content alone leaves the full composition (or
     * legacy HTML) available to renderers which prefer those representations.
     */
    public function project(array $post)
    {
        $body = $this->body($post);
        if (!$this->getObject('publishingpolicy', 'simpleblog')->canRead($post)) {
            $post['composition_json'] = null;
            $post['legacy_content_html'] = null;
        }
        $post['post_content'] = $body;
        return $post;
    }

    public function body(array $post, $editorPreview = false)
    {
        $policy = $this->getObject('publishingpolicy', 'simpleblog');
        $full = ($editorPreview && $policy->canEdit($post)) || $policy->canRead($post);
        if (!$full && !$policy->canDiscover($post)) return '';
        $html = !empty($post['composition_json'])
            ? $this->getObject('compositionservice','contentblocks')->render($this->getObject('compositionservice','contentblocks')->fromPost($post))
            : $this->getObject('richtextsanitizer','utilities')->cleanHtml($post['post_content']);
        if ($full) return $html;
        $percent = $this->getObject('dbsysconfig','sysconfig')->getValue('SIMPLEBLOG_RESTRICTED_PREVIEW_PERCENT','simpleblog');
        return self::excerpt($html, is_numeric($percent) ? (float)$percent : 0);
    }

    /** Complete blocks only. Always reserve a final block for restricted content. */
    public static function excerpt($html, $percent)
    {
        if ($percent <= 0 || trim($html) === '') return '';
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
            $body = $document->getElementsByTagName('body')->item(0);
            $blocks = [];
            // Unwrap structural composition wrappers, retaining each semantic block intact.
            $collect = function ($node) use (&$collect, &$blocks) {
                if ($node instanceof DOMElement && (in_array(strtolower($node->tagName), ['body','section'], true)
                    || (strtolower($node->tagName)==='div' && !$node->getElementsByTagName('img')->length && !$node->getElementsByTagName('iframe')->length))) {
                    foreach ($node->childNodes as $child) $collect($child);
                } elseif ($node instanceof DOMElement || ($node instanceof DOMText && trim($node->textContent) !== '')) {
                    $blocks[] = $node;
                }
            };
            if ($body) $collect($body);
            if (count($blocks) < 2) return '';
            $weight = static fn($node) => max(1, mb_strlen(trim($node->textContent)));
            $target = array_sum(array_map($weight, $blocks)) * min(99, $percent) / 100;
            $result = ''; $count = 0;
            foreach (array_slice($blocks, 0, -1) as $block) {
                $result .= $document->saveHTML($block);
                $count += $weight($block);
                if ($count >= $target) break;
            }
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function cta(array $post)
    {
        if (empty($post['required_tier_code']) || $this->getObject('user','security')->isLoggedIn()) return '';
        $config = $this->getObject('dbsysconfig','sysconfig');
        $url = trim((string)$config->getValue('SIMPLEBLOG_RESTRICTED_CTA_URL','simpleblog'));
        if ($url === '') {
            $product=$this->getObject('paymentcatalogservice','payment-service')->monthlyMembershipProduct($post['required_tier_code']);
            if ($product) {
                $return=html_entity_decode($this->uri(['action'=>'catalogue','product'=>$product['code']],'payment-service'),ENT_QUOTES,'UTF-8');
                $parts=parse_url($return);
                $return=($parts['path']??'/index.php').(isset($parts['query'])?'?'.$parts['query']:'');
                $url=$this->uri(['return_to'=>$return],'registration-service');
            } else $url = $this->uri(['action'=>'tiers'], 'payment-service');
        }
        if (!preg_match('~^(?:https?://|/(?!/)|index\.php\?)~i', $url)) return '';
        $r = $this->getObject('publishingrenderer','simpleblog');
        return '<aside class="chisimba-guidance-card"><p>'.publishingrenderer::escape($r->text('restricted_notice')).'</p><a class="button chisimba-button-primary" href="'.publishingrenderer::escape($url).'">'.publishingrenderer::escape($r->text('join')).'</a></aside>';
    }
}
