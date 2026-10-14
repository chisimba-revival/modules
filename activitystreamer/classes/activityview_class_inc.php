<?php
/** Native presentation shared by the page and retained block names. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class activityview extends ChisimbaObject
{
    public function text(string $key): string
    {
        return ucfirst($this->getObject('language','language')->code2Txt('mod_activitystreamer_native_'.$key,'activitystreamer'));
    }
    private function escape($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    public function table(array $data): string
    {
        $e = fn($v) => $this->escape($v);
        $html = '<div class="chisimba-table-wrap" tabindex="0" role="region" aria-label="'.$e($this->text('results')).'"><table class="chisimba-table"><thead><tr>';
        foreach (['title','by','date'] as $key) $html .= '<th scope="col">'.$e($this->text($key)).'</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($data['rows'] as $row) {
            $title = $e($row['title']);
            if ($data['mode'] === 'pages') $title = '<a href="'.$e($this->uri(['action'=>'viewpage','id'=>$row['id']], 'contextcontent')).'">'.$title.'</a>';
            $html .= '<tr><td>'.$title;
            if ((string) ($row['description'] ?? '') !== '') $html .= '<p>'.$e($row['description']).'</p>';
            $html .= '</td><td>'.$e(trim($row['createdby']) ?: $this->text('unknown')).'</td><td>'.$e($row['createdon']).'</td></tr>';
        }
        if (!$data['rows']) $html .= '<tr><td colspan="3">'.$e($this->text('empty')).'</td></tr>';
        return $html.'</tbody></table></div>';
    }
    public function block(string $mode, bool $linkOnly = false): string
    {
        $browser = $this->getObject('activitybrowser','activitystreamer');
        if (!$browser->mayView($mode)) return '';
        $url = $this->escape($this->uri(['mode'=>$mode], 'activitystreamer'));
        $link = '<a class="button chisimba-button-secondary" href="'.$url.'">'.$this->escape($this->text('browse')).'</a>';
        return $linkOnly ? $link : $this->table($browser->browse($mode, [], 5)).'<div class="chisimba-form-actions">'.$link.'</div>';
    }
}
