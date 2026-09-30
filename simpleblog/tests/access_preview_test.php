<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public function getObject($name, $module = null) { return $GLOBALS['objects'][$name]; }
}
require dirname(__DIR__).'/classes/accesspreview_class_inc.php';
require dirname(__DIR__).'/classes/publishingrenderer_class_inc.php';
function verify($ok,$message) { if (!$ok) throw new RuntimeException($message); }
$html='<section><div><p>First paragraph with <strong>formatting</strong>.</p><p>Second paragraph.</p><p>Final secret marker.</p></div></section>';
$preview=accesspreview::excerpt($html,30);
verify(str_contains($preview,'</strong>'),'Formatting retained');
verify(!str_contains($preview,'Final secret'),'Secret omitted');
verify(accesspreview::excerpt($html,0)==='','Preview can be disabled');
verify(!str_contains(accesspreview::excerpt($html,100),'Final secret'),'Always reserve final block');
verify(accesspreview::excerpt('<p>Only one indivisible block.</p>',30)==='','Single block fails closed');
verify(!str_contains(accesspreview::excerpt('<p>日本語の文章です。</p><p>秘密</p>',30),'秘密'),'Unicode boundary');
$parsed=new DOMDocument();verify($parsed->loadHTML($preview),'Preview parses');
echo "PASS: balanced previews, configurable percentage, Unicode, single-block and final-block protection\n";

$policy = new class {
    public $read = false, $discover = true;
    public function canRead($post) { return $this->read; }
    public function canDiscover($post) { return $this->discover; }
};
$GLOBALS['objects'] = [
    'publishingpolicy' => $policy,
    'dbsysconfig' => new class { public function getValue($key, $module) { return 30; } },
    'richtextsanitizer' => new class { public function cleanHtml($html) { return $html; } },
    'compositionservice' => new class {
        public function fromPost($post) { return json_decode($post['composition_json'], true); }
        public function render($composition) { return implode('', array_column($composition['blocks'], 'text')); }
    },
];
$post = [
    'id' => 'projection-test', 'required_tier_code' => 'tier_1',
    'post_content' => '<p>RAW_SECRET</p>', 'legacy_content_html' => '<p>LEGACY_SECRET</p>',
    'composition_json' => json_encode(['version'=>1, 'blocks'=>[
        ['type'=>'text', 'text'=>'<p>Visible introduction.</p>'],
        ['type'=>'text', 'text'=>'<p>COMPOSITION_SECRET</p>'],
    ]]),
];
$service = new accesspreview();
$projected = $service->project($post);
verify(str_contains($projected['post_content'], 'Visible introduction'), 'Allowed preview retained');
verify(!str_contains(json_encode($projected), 'SECRET'), 'All restricted body representations removed');
verify(!str_contains(publishingrenderer::excerpt($projected), 'SECRET'), 'Structured listing summary cannot recover hidden composition');
verify($projected['required_tier_code'] === 'tier_1' && $projected['id'] === $post['id'], 'Identity and access metadata retained');
verify(str_contains($post['composition_json'], 'COMPOSITION_SECRET'), 'Projection does not mutate stored input');
$policy->discover = false;
verify($service->project($post)['post_content'] === '', 'Undiscoverable posts have no body');
$policy->read = true;
$projected = $service->project($post);
verify($projected['composition_json'] === $post['composition_json'], 'Authorised readers retain structured summaries');
verify(str_contains($projected['post_content'], 'COMPOSITION_SECRET'), 'Authorised readers receive full content');
verify(str_contains(publishingrenderer::excerpt($projected), 'COMPOSITION_SECRET'), 'Authorised structured summary retained');
$template = file_get_contents(dirname(__DIR__).'/templates/content/article_tpl.php');
verify(str_contains($template, 'excerpt($readerPost)') && !str_contains($template, 'excerpt($post)'), 'Metadata uses only the reader projection');
echo "PASS: reader projection strips alternate restricted bodies and preserves authorised compositions\n";
