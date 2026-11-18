<?php
/** Authorised, revision-bound generation of courses from semantic maps. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class knowledgemapcourseservice extends ChisimbaObject
{
    private $maps, $service, $graph, $blueprints, $contexts, $authors, $user, $outline, $pages, $quizzes, $modules, $blocks;
    public function init()
    {
        $this->maps=$this->getObject('dbknowledgemaps');
        $this->service=$this->getObject('knowledgemapservice');
        $this->graph=$this->getObject('knowledgemapgraphservice');
        $this->blueprints=$this->getObject('knowledgemapblueprint');
        $this->contexts=$this->getObject('dbcontext','context');
        $this->authors=$this->getObject('contextauthorservice','contextadmin');
        $this->user=$this->getObject('user','security');
        $this->outline=$this->getObject('dbcourseoutline','contextcontent');
        $this->pages=$this->getObject('contentauthoringservice','contextcontent');
        $this->quizzes=$this->getObject('quizoutlineservice','mcqtests');
        $this->modules=$this->getObject('dbcontextmodules','context');
        $this->blocks=$this->getObject('dbcontextblocks','context');
    }

    public function canCreate()
    { return $this->authors->canCreateContext($this->user->userId()); }

    /** Preview only persisted maps; browser drafts must be saved before requesting this. */
    public function preview($id, $revision)
    {
        if (!$this->canCreate() || !$this->service->map($id,'manage')) throw new DomainException('forbidden');
        $document=$this->service->document($id,'manage');
        if (!$document || (int)$document['map']['revision'] !== $revision) throw new DomainException('stale');
        $normal=$this->graph->normalise($document);
        if (!$normal['valid']) throw new DomainException('invalid');
        return $this->blueprints->build($normal['document']);
    }

    /** Atomically provision identity, memberships, outline and server-owned provenance. */
    public function generate($id, $revision, $requestedCode)
    {
        $preview=$this->preview($id,$revision);
        if (!$preview['valid']) throw new DomainException('invalid');
        $code=strtolower(trim($requestedCode));
        // dbcontext strips punctuation; accept its canonical alphabet to avoid aliases.
        if (!preg_match('/^[a-z0-9]{1,32}$/D',$code) || $code==='root') throw new DomainException('code');
        $result=$this->maps->transaction(function () use ($id,$revision,$code,$preview) {
            $map=$this->maps->lockAtRevision($id,$revision);
            if (!$map) throw new DomainException('stale');
            if (!$this->canCreate() || !$this->service->map($id,'manage')) throw new DomainException('forbidden');
            $metadata=json_decode((string)$map['sourcemetadata'],true);
            if (!is_array($metadata)) $metadata=[];
            $previous=$metadata['courseBlueprint']??null;
            $oldCode=$previous['contextCode']??'';
            $old=$oldCode!==''?$this->contexts->getContext($oldCode):false;
            if ($old) {
                if (!$this->authors->canManageContext($oldCode,$this->user->userId())) throw new DomainException('forbidden');
                if ($code!==$oldCode) throw new DomainException('linked');
                $state=$this->snapshot($code,true);
                if (!empty($previous['locked']) || !hash_equals((string)($previous['fingerprint']??''),$this->fingerprint($state))) {
                    // Commit the permanent guard even though no generation takes place.
                    $metadata['courseBlueprint']['locked']=true;
                    if (!$this->maps->updateMap($id,['sourcemetadata'=>json_encode($metadata)])) throw new RuntimeException('Provenance update failed');
                    return ['blocked'=>true];
                }
                $this->outline->removeUntouched($state['outline']);
                $this->quizzes->removeEmpty($state['quizzes']);
                if ($this->contexts->update('contextcode',$code,['title'=>$preview['outline']['title'],
                    'menutext'=>$preview['outline']['title'],'about'=>$this->outline->plainHtml($preview['outline']['description']),
                    'use_sections'=>$this->hasSections($preview['outline'])?1:0])===false) throw new RuntimeException('Context update failed');
            } else {
                if ($this->outline->contextSnapshot($code,true)) throw new DomainException('exists');
                // Legacy deletion can leave module-owned rows. Never revive or adopt them.
                $unused=$this->snapshot($code,true);
                foreach(array_merge($unused['outline'],$unused['quizzes']) as $rows) if($rows) throw new DomainException('exists');
                if (!$this->contexts->createContext($code,$preview['outline']['title'],'Unpublished','Private',
                    $this->outline->plainHtml($preview['outline']['description']),false,0,'0','','standard','free',false,
                    'private',null,$this->hasSections($preview['outline']),false)) throw new RuntimeException('Context creation failed');
                foreach (['contextcontent','mcqtests'] as $module) if (!$this->modules->addModule($code,$module)) throw new RuntimeException('Module activation failed');
                foreach (['learningjourney','aboutcontext'] as $block) if (!$this->blocks->addBlock('block|'.$block.'|context','middle',$code,'context')) throw new RuntimeException('Context block creation failed');
            }
            $position=0;
            $this->writeChildren($code,$preview['outline']['children'],'','',$position);
            $metadata['courseBlueprint']=['contextCode'=>$code,'revision'=>$revision,'locked'=>$preview['hasContent'],
                'fingerprint'=>$this->fingerprint($this->snapshot($code,true))];
            if (!$this->maps->updateMap($id,['sourcemetadata'=>json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)])) throw new RuntimeException('Provenance update failed');
            return ['contextCode'=>$code,'blocked'=>false];
        });
        if ($result['blocked']) throw new DomainException('locked');
        // The course is deliberately unpublished. Indexing belongs to its normal publication workflow.
        return $result;
    }

    /** Show the existing linked code without permitting a client to replace that link. */
    public function linkedCode($id)
    {
        $map=$this->service->map($id,'manage');
        if (!$map) return '';
        $metadata=json_decode((string)$map['sourcemetadata'],true);
        $code=$metadata['courseBlueprint']['contextCode']??'';
        return $code!=='' && $this->contexts->getContext($code) ? $code : '';
    }

    private function writeChildren($code, array $items, $section, $chapter, &$position)
    {
        foreach ($items as $item) {
            if (in_array($item['role'],['section','chapter'],true)) {
                $id=$this->outline->createContainer($code,$item,$this->user->userId(),++$position,$section);
                $this->writeChildren($code,$item['children'],$item['role']==='section'?$id:$section,$item['role']==='chapter'?$id:'',$position);
            } else {
                $quiz=$item['role']==='quiz'?$this->quizzes->createEmpty($code,$chapter,$item['title'],$this->user->userId()):'';
                if ($quiz!=='' && trim($item['quizInput']??'')!=='') $this->getObject('quizinputjobs','mcqtests')->enqueue($code,$quiz,$item['quizInput'],$item['quizSettings']??null);
                $this->pages->createNativePage(['contextcode'=>$code,'chapterid'=>$chapter,'parentid'=>'root',
                    'title'=>$item['title'],'body'=>$this->outline->plainHtml($item['body']),
                    'contenttype'=>$quiz!==''?'assessment_activity':'rich_text','providermodule'=>$quiz!==''?'mcqtests':'',
                    'provideritemid'=>$quiz,'manage_transaction'=>false,'defer_effects'=>true]);
            }
        }
    }

    private function hasSections(array $outline)
    { foreach ($outline['children'] as $child) if ($child['role']==='section') return true; return false; }

    private function snapshot($code,$lock)
    {
        $context=$this->outline->contextSnapshot($code,$lock);
        // Visiting a course must not make an otherwise untouched outline non-regenerable.
        unset($context['lastaccessed']);
        return ['context'=>$context,'outline'=>$this->outline->snapshot($code,$lock),'quizzes'=>$this->quizzes->snapshot($code,$lock)];
    }
    private function fingerprint(array $state) { return hash('sha256',json_encode($state,JSON_THROW_ON_ERROR)); }
}
