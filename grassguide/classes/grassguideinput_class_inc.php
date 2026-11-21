<?php
/** Field-guide input contracts. No network requests or persistence. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguideinput extends ChisimbaObject
{
    public const ECOLOGY = ['Decreaser', 'Increaser I', 'Increaser II', 'Increaser III', 'Invader'];
    public const LIFE = ['Annual', 'Perennial', 'Annual / Perennial'];
    public const GRAZING = ['Low', 'Average', 'High'];
    public const HEADS = ['open_panicle', 'contracted_panicle', 'digitate', 'racemes_axis', 'single_raceme', 'paired_racemes', 'compound', 'capitate'];
    public const ROLES = ['habit', 'inflorescence', 'spikelet', 'leaf', 'ligule', 'other'];
    public const LICENCES = ['CC0-1.0', 'CC-BY-4.0', 'CC-BY-SA-4.0', 'CC-BY-NC-4.0', 'CC-BY-NC-SA-4.0', 'CC-BY-3.0', 'CC-BY-SA-3.0', 'CC-BY-NC-3.0', 'CC-BY-NC-SA-3.0', 'permission', 'YouTube'];
    public function init() {}

    public static function text(array $input, string $key, int $limit, bool $required = false): string
    {
        $v = $input[$key] ?? '';
        if (!is_string($v) || !mb_check_encoding($v, 'UTF-8')) throw new DomainException('invalid');
        $v = trim($v);
        if (mb_strlen($v) > $limit || ($required && $v === '') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $v)) throw new DomainException('invalid');
        return $v;
    }

    public static function choice(array $input, string $key, array $choices, bool $optional = true): string
    {
        $v = self::text($input, $key, 80);
        if (($optional && $v === '') || in_array($v, $choices, true)) return $v;
        throw new DomainException('invalid');
    }

    public static function url(array $input, string $key, bool $required = false): string
    {
        $v = self::text($input, $key, 1500, $required);
        if ($v === '') return '';
        $p = parse_url($v);
        if (!filter_var($v, FILTER_VALIDATE_URL) || ($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass'])) throw new DomainException('invalid_url');
        return $v;
    }

    public static function species(array $input): array
    {
        $data = [];
        foreach (['genus'=>100, 'epithet'=>100, 'authority'=>250, 'common_name'=>200, 'other_names'=>1500, 'synonyms'=>3000, 'description'=>12000, 'diagnostic'=>12000, 'similar'=>6000, 'habitat'=>6000, 'succession'=>120, 'ecology_source'=>3000, 'trait_source'=>3000, 'editor_notes'=>6000, 'powo_id'=>200, 'taxonomy_source'=>3000] as $key=>$limit) {
            $data[$key] = self::text($input, $key, $limit, in_array($key, ['genus','epithet','common_name'], true));
        }
        if (!preg_match('/^[A-Z][a-z]+$/D', $data['genus']) || !preg_match('/^[a-z][a-z-]+$/D', $data['epithet'])) throw new DomainException('invalid_name');
        if ($data['powo_id'] !== '' && !preg_match('/^urn:lsid:ipni\.org:names:[0-9]+-[0-9]+$/D', $data['powo_id'])) throw new DomainException('invalid_powo');
        $data['ecological_status'] = self::choice($input, 'ecological_status', self::ECOLOGY);
        $data['life_cycle'] = self::choice($input, 'life_cycle', self::LIFE);
        $data['grazing_value'] = self::choice($input, 'grazing_value', self::GRAZING);
        $data['ecology_review'] = self::choice($input, 'ecology_review', ['pending','reviewed'], false);
        $data['taxonomy_review'] = self::choice($input, 'taxonomy_review', ['reported','pending','reviewed'], false);
        $data['state'] = self::choice($input, 'state', ['draft','reviewed','archived'], false);
        $data['awns'] = self::choice($input, 'awns', ['present','absent','variable']);
        $heads = $input['heads'] ?? [];
        if (!is_array($heads) || count($heads) > count(self::HEADS)) throw new DomainException('invalid');
        foreach ($heads as $head) if (!is_string($head) || !in_array($head, self::HEADS, true)) throw new DomainException('invalid');
        $data['heads'] = array_values(array_unique($heads));
        $date = self::text($input, 'taxonomy_checked', 10);
        if ($date !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)) || $date > gmdate('Y-m-d'))) throw new DomainException('invalid_date');
        $data['taxonomy_checked'] = $date;
        if ($data['taxonomy_review'] === 'reviewed' && ($date === '' || $data['powo_id'] === '' || $data['taxonomy_source'] === '')) throw new DomainException('taxonomy_evidence');
        if ($data['ecology_review'] === 'reviewed' && $data['ecology_source'] === '') throw new DomainException('ecology_evidence');
        if (($data['heads'] || $data['awns'] !== '') && $data['trait_source'] === '') throw new DomainException('trait_evidence');
        if ($data['state'] === 'reviewed' && ($data['diagnostic'] === '' || $data['taxonomy_review'] !== 'reviewed' || $data['ecology_review'] !== 'reviewed')) throw new DomainException('review_incomplete');
        return $data;
    }

    public static function media(array $input): array
    {
        $data = [];
        $data['kind'] = self::choice($input, 'kind', ['image','youtube'], false);
        $data['role'] = self::choice($input, 'role', self::ROLES, false);
        $data['state'] = self::choice($input, 'state', ['active','archived'], false);
        foreach (['caption'=>1000, 'alt_text'=>1000, 'creator'=>300, 'rights_note'=>3000] as $key=>$limit) $data[$key] = self::text($input, $key, $limit, $key === 'creator' || ($key === 'alt_text' && $data['kind'] === 'image'));
        $data['source_url'] = self::url($input, 'source_url', true);
        $data['licence'] = self::choice($input, 'licence', self::LICENCES, false);
        $data['image_url'] = $data['kind'] === 'image' ? self::url($input, 'image_url', true) : '';
        $data['youtube_id'] = $data['kind'] === 'youtube' ? self::text($input, 'youtube_id', 11, true) : '';
        if ($data['kind'] === 'youtube' && (!preg_match('/^[a-zA-Z0-9_-]{11}$/D', $data['youtube_id']) || $data['licence'] !== 'YouTube')) throw new DomainException('invalid_video');
        if ($data['kind'] === 'image' && $data['licence'] === 'YouTube') throw new DomainException('invalid');
        if ($data['licence'] === 'permission' && $data['rights_note'] === '') throw new DomainException('rights_required');
        return $data;
    }

    /** Strict bundled catalogue reader. This does not validate names online. */
    public static function seed(string $path): array
    {
        $stream = fopen($path, 'rb');
        if (!$stream) throw new RuntimeException('seed_missing');
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            $expected = ['Accepted_Genus','Accepted_Species','Authority','Taxonomic_Status','Main_Common_Name','Other_Common_Name_1','Other_Common_Name_2','Other_Common_Name_3','Synonyms','Ecological_Status','Succession_Stage','Life_Cycle','Grazing_Value'];
            if ($header !== $expected) throw new DomainException('invalid_seed');
            $rows = [];
            while (($raw = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                if (count($raw) !== count($header)) throw new DomainException('invalid_seed');
                $r = array_combine($header, $raw);
                $key = $r['Accepted_Genus'].' '.$r['Accepted_Species'];
                if (isset($rows[$key]) || $r['Taxonomic_Status'] !== 'Accepted') throw new DomainException('invalid_seed');
                $rows[$key] = self::species([
                    'genus'=>$r['Accepted_Genus'], 'epithet'=>$r['Accepted_Species'], 'authority'=>$r['Authority'], 'common_name'=>$r['Main_Common_Name'],
                    'other_names'=>implode("\n",array_filter([$r['Other_Common_Name_1'],$r['Other_Common_Name_2'],$r['Other_Common_Name_3']])),
                    'synonyms'=>str_replace('|',"\n",$r['Synonyms']), 'ecological_status'=>$r['Ecological_Status'], 'succession'=>$r['Succession_Stage'],
                    'life_cycle'=>$r['Life_Cycle'], 'grazing_value'=>$r['Grazing_Value'], 'ecology_review'=>'pending', 'taxonomy_review'=>'reported', 'state'=>'draft',
                ]);
            }
            return $rows;
        } finally { fclose($stream); }
    }
}
