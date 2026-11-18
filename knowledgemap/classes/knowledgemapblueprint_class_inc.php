<?php
/** Translate explicit semantic nodes into a portable course blueprint. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class knowledgemapblueprint extends ChisimbaObject
{
    const ROLES = ['course','section','chapter','page','quiz','description','content','quiz_input'];

    /** Only explicit content roles and Quiz notes become generated content. */
    public function build(array $graph)
    {
        $nodes = array_column($graph['nodes'], null, 'id');
        $children = []; $parents = []; $errors = []; $excluded = [];
        $relationships = $graph['relationships'];
        usort($relationships, fn($a,$b) => ($a['order'] <=> $b['order']) ?: strcmp($a['id'],$b['id']));
        foreach ($relationships as $edge) if ($edge['type'] === 'contains') {
            $children[$edge['from']][] = $edge['to']; $parents[$edge['to']] = $edge['from'];
        }
        $root = $nodes[$graph['rootId']];
        if ($root['type'] !== 'course' || isset($parents[$root['id']])) $errors[] = ['root', $root['title']];
        $allowed = ['course'=>['section','chapter','page','description'], 'section'=>['chapter','page','description'],
            'chapter'=>['page','quiz','description'], 'page'=>['content'], 'quiz'=>['quiz_input'], 'description'=>[], 'content'=>[], 'quiz_input'=>[]];
        foreach ($nodes as $node) {
            if (!in_array($node['type'], self::ROLES, true)) { $excluded[] = $node['title']; continue; }
            if ($node['type'] === 'quiz' && mb_strlen($node['title']) > 60) $errors[] = ['quiz_title', $node['title']];
            if (trim($node['title']) === '') $errors[] = ['title', $node['id']];
            if ($node['id'] === $root['id']) continue;
            $parent = $nodes[$parents[$node['id']] ?? ''] ?? null;
            if (!$parent || !in_array($node['type'], $allowed[$parent['type']] ?? [], true)) $errors[] = ['parent', $node['title']];
            $cursor = $node['id']; $seen = [];
            while (isset($parents[$cursor]) && !isset($seen[$cursor])) { $seen[$cursor]=true; $cursor=$parents[$cursor]; }
            if ($cursor !== $root['id']) $errors[] = ['detached', $node['title']];
        }
        $hasContent = false;
        $convert = function ($id) use (&$convert, &$hasContent, &$errors, $nodes, $children) {
            $node = $nodes[$id];
            $item = ['sourceId'=>$id,'role'=>$node['type'],'title'=>$node['title'],'description'=>'','body'=>'','quizInput'=>'','children'=>[], 'generated'=>false];
            // A Quiz's own Note is its source; legacy input children remain readable.
            if ($node['type']==='quiz' && trim($node['description'])!=='') {
                $item['quizInput']=$node['description']; $hasContent=true;
            }
            foreach ($children[$id] ?? [] as $childId) {
                $child = $nodes[$childId];
                if (!in_array($child['type'], self::ROLES, true)) continue;
                if (in_array($child['type'], ['description','content','quiz_input'], true)) {
                    // A tagged node's title is its text; an explicit note can hold longer text.
                    $text = trim($child['description']) !== '' ? $child['description'] : $child['title'];
                    $field = ($child['type'] === 'description' ? 'description' : ($child['type']==='quiz_input'?'quizInput':'body'));
                    if ($field === 'description' && $item[$field] !== '') $errors[] = ['description', $node['title']];
                    $item[$field] .= ($item[$field] === '' ? '' : "\n\n") . $text;
                    $hasContent = $hasContent || trim($text) !== '';
                } else $item['children'][] = $convert($childId);
            }
            if (in_array($item['role'], ['course','section'], true)) {
                // Consecutive direct pages get a container, preserving their position among chapters.
                $grouped = []; $container = null; $number = 0;
                foreach ($item['children'] as $child) {
                    if (in_array($child['role'], ['page'], true)) {
                        if ($container === null) {
                            $container = count($grouped); ++$number;
                            $grouped[] = ['sourceId'=>$id.':container:'.$number,'role'=>'chapter','title'=>$item['title'],
                                'description'=>'','body'=>'','quizInput'=>'','children'=>[], 'generated'=>true];
                        }
                        $grouped[$container]['children'][] = $child;
                    } else { $container = null; $grouped[] = $child; }
                }
                $item['children'] = $grouped;
            }
            if ($item['role']==='chapter') {
                $item['children']=array_merge(array_values(array_filter($item['children'],fn($child)=>$child['role']!=='quiz')),array_values(array_filter($item['children'],fn($child)=>$child['role']==='quiz')));
            }
            if ($item['role']==='quiz' && trim($item['quizInput'])!=='') {
                try { $plan=$this->getObject('quizinputplan','mcqtests')->build($item['quizInput']);$item['quizQuestionCount']=$plan['count']; }
                catch (InvalidArgumentException $error) { $errors[]=['quiz_input',$node['title']]; }
            }
            return $item;
        };
        $outline = $errors ? null : $convert($root['id']);
        return ['valid'=>!$errors,'errors'=>$errors,'excluded'=>$excluded,'outline'=>$outline,'hasContent'=>$hasContent];
    }
}
