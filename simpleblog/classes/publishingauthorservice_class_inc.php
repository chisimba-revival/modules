<?php
/** Editorial credit is independent of ownership and publishing permissions. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class publishingauthorservice extends ChisimbaObject
{
    public function init() {}
    private function hasWebinars(): bool
    { return $this->getObject('modules','modulecatalogue')->checkIfRegistered('webinar'); }
    public function person(string $kind, string $id): ?array
    {
        if ($kind==='user') {
            $row=$this->getObject('userservice','security')->findByUserId($id);
            if (!$row || (string)$row['isactive']!=='1') return null;
            $bio=$this->getObject('authorbiographyservice','userdetails')->publicPerson($id);
            return ['kind'=>'user','id'=>$id,'name'=>trim($row['firstname'].' '.$row['surname']),
                'url'=>$bio ? html_entity_decode($this->uri(['action'=>'biography','userid'=>$id],'userdetails'),ENT_QUOTES,'UTF-8') : ''];
        }
        if ($kind==='speaker' && $this->hasWebinars()) {
            $row=$this->getObject('webinarstore','webinar')->one($id);
            if (!$row || $row['kind']!=='speaker') return null;
            return ['kind'=>'speaker','id'=>$id,'name'=>html_entity_decode($row['title'],ENT_QUOTES|ENT_HTML5,'UTF-8'),
                'url'=>html_entity_decode($this->uri(['action'=>'view','id'=>$id],'webinar'),ENT_QUOTES,'UTF-8')];
        }
        return null;
    }
    /** Authorised editor endpoint only; explicit choices avoid ambiguous name matching. */
    public function suggestions(string $query): array
    {
        $query=trim($query);
        if (mb_strlen($query)<2 || mb_strlen($query)>120) return [];
        $result=[];
        foreach ($this->getObject('userservice','security')->authorNameSuggestions($query) as $row) {
            $person=$this->person('user',(string)$row['userid']);
            if ($person) $result[]=$person;
        }
        if ($this->hasWebinars()) foreach ($this->getObject('webinarstore','webinar')->speakerNameSuggestions($query) as $row) {
            $person=$this->person('speaker',$row['id']);
            if ($person) $result[]=$person;
        }
        usort($result,static fn($a,$b)=>strcasecmp($a['name'],$b['name']) ?: strcmp($a['kind'].$a['id'],$b['kind'].$b['id']));
        return array_slice($result,0,20);
    }
    public function values(array $input, ?array $old): array
    {
        foreach (['author_credit','author_kind','author_id'] as $key)
            if (isset($input[$key]) && !is_string($input[$key])) throw new DomainException('invalid_author');
        $name=trim($input['author_credit']??'');$kind=$input['author_kind']??'';$id=$input['author_id']??'';
        if (mb_strlen($name)>250 || preg_match('/[\x00-\x1f\x7f]/u',$name)) throw new DomainException('invalid_author');
        if ($name==='') return ['author_credit'=>null,'author_kind'=>null,'author_id'=>null];
        if ($kind==='' && $id==='') return ['author_credit'=>$name,'author_kind'=>null,'author_id'=>null];
        if (!in_array($kind,['user','speaker'],true) || $id==='' || strlen($id)>32) throw new DomainException('invalid_author');
        // Keep an existing credit if its target has since become unavailable.
        $unchanged=$name===($old['author_credit']??'') && $kind===($old['author_kind']??'') && $id===($old['author_id']??'');
        if (!$unchanged) {
            $person=$this->person($kind,$id);
            if (!$person || $person['name']!==$name) throw new DomainException('invalid_author');
        }
        return ['author_credit'=>$name,'author_kind'=>$kind,'author_id'=>$id];
    }
    public function link(array $post): string
    {
        $kind=$post['author_kind']??'';$id=$post['author_id']??'';
        if ($kind==='' || $id==='') return '';
        return $this->person($kind,$id)['url']??'';
    }
}
