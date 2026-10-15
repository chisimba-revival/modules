<?php
/** Slate's domain adapter to the shared native block editor. Dispatch guards run first. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class slateblockeditor extends ChisimbaObject
{
    public function init() {}
    public function handle(string $action, array $input): array
    {
        try {
            $repo = $this->getObject('dbslateblocks','slate');
            if ($action === 'renderblock') {
                $parts = explode('|', $input['blockid']);
                $html = $parts[0] === 'block'
                    ? $this->getObject('blocks','blocks')->showBlock($parts[1], $parts[2], null, 20, true, false)
                    : $this->getObject('dynamicblocks','blocks')->showBlock($parts[1]);
                return ['ok'=>true, 'status'=>200, 'html'=>'<div class="block">'.$html.'</div>'];
            }
            $revision = $repo->editPlacement($input['pageId'], $input['revision'], $action, $input['blockid'], $input['side'], $input['direction']);
            return ['ok'=>true, 'status'=>200, 'revision'=>$revision];
        } catch (Throwable $error) {
            $code = in_array($error->getMessage(), ['block_stale','block_denied','editor_missing'], true) ? 'block_stale' : 'block_uncertain';
            return ['ok'=>false, 'status'=>409, 'code'=>$code, 'message'=>html_entity_decode($this->getObject('language','language')->languageText('mod_context_'.$code,'context'), ENT_QUOTES, 'UTF-8')];
        }
    }
    public function settings(string $key, string $revision): string
    {
        $language = $this->getObject('language','language');
        $attributes = ['module'=>'slate','pageid'=>$key,'revision'=>$revision,
            'csrf'=>$this->getObject('slatesecurity','slate')->csrf()->issue('slate_edit'),
            'cancel'=>$language->languageText('word_cancel')];
        foreach (['saving','saved','failed','stale','uncertain','denied','method','csrf-error'] as $name) {
            $attributes[$name] = $language->languageText('mod_context_block_'.($name === 'csrf-error' ? 'csrf' : $name), 'context');
        }
        $html = '<div id="block-editor-settings"';
        foreach ($attributes as $name=>$value) $html .= ' data-'.$name.'="'.htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8').'"';
        return $html.'><p id="block-editor-status" role="status" aria-live="polite"></p></div>';
    }
}
