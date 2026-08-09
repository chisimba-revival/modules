<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); }

class block_faq26_center extends ChisimbaObject
{
    public function show($scopeType = 'global', $scopeId = 'global', $canManage = false)
    {
        $service = $this->getObject('faq26service', 'faq26');
        $faqs = $service->getFaqsForScope($scopeType, $scopeId, $canManage);

        $addUrl = $this->uri(array('action' => 'add'), 'faq26');

        $html = '<div class="faq26-center-block p-4 bg-white rounded-lg shadow-sm border border-slate-200">';
        $html .= '<div class="flex justify-between items-center mb-4">';
        $html .= '<h3 class="text-lg font-semibold text-slate-800">Frequently Asked Questions</h3>';
        if ($canManage) {
            $html .= '<a href="' . $addUrl . '" class="btn btn-sm btn-primary">+ Add FAQ</a>';
        }
        $html .= '</div>';

        if (empty($faqs)) {
            $html .= '<p class="text-slate-500 text-sm italic">No questions available for this section.</p>';
        } else {
            $html .= '<div class="space-y-3">';
            foreach ($faqs as $faq) {
                $html .= '<details class="group border border-slate-200 rounded-md p-3 transition-all">';
                $html .= '<summary class="font-medium text-slate-700 cursor-pointer flex justify-between items-center list-none">';
                $html .= '<span>' . htmlentities($faq['question']) . '</span>';
                $html .= '<span class="transition group-open:rotate-180">▼</span>';
                $html .= '</summary>';
                $html .= '<div class="mt-2 text-sm text-slate-600 border-t border-slate-100 pt-2">';
                $html .= nl2br(htmlentities($faq['answer']));
                $html .= '</div>';
                $html .= '</details>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
?>
