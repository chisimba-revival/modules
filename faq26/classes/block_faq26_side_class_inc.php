<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('Direct access denied'); }

class block_faq26_side extends ChisimbaObject
{
    public function show($scopeType = 'global', $scopeId = 'global')
    {
        $service = $this->getObject('faq26service', 'faq26');
        $faqs = $service->getFaqsForScope($scopeType, $scopeId, false);

        $html = '<div class="faq26-side-block p-3 bg-slate-50 rounded-md border border-slate-200 text-sm">';
        $html .= '<h4 class="font-semibold text-slate-700 mb-2 border-b pb-1">Quick FAQs</h4>';

        if (empty($faqs)) {
            $html .= '<p class="text-slate-400 text-xs italic">No FAQs listed.</p>';
        } else {
            $topFaqs = array_slice($faqs, 0, 3);
            $html .= '<ul class="space-y-2 text-xs">';
            foreach ($topFaqs as $faq) {
                $html .= '<li>';
                $html .= '<span class="font-medium text-slate-800 block">' . htmlentities($faq['question']) . '</span>';
                $html .= '<span class="text-slate-500 line-clamp-2">' . htmlentities(substr($faq['answer'], 0, 80)) . '...</span>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
        return $html;
    }
}
?>
