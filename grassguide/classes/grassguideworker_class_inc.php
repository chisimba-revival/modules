<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguideworker extends ChisimbaObject
{
    public function init() {}
    public function runOne(string $provider): array
    {
        if(PHP_SAPI!=='cli')throw new DomainException('CLI only');
        $apis=$this->getObject('grassguideproviders','grassguide');
        if(!isset(grassguideproviders::HOSTS[$provider]))throw new InvalidArgumentException('Unknown provider');
        $store=$this->getObject('grassguidestore','grassguide');
        return $store->withDatabaseLock('grass-api:'.$provider,function() use($provider,$store,$apis) {
            $job=$store->nextEnrichment($provider);if(!$job)return ['selected'=>false];
            try {
                $row=$store->one($job['species_id']);if(!$row)throw new DomainException('missing');
                $species=grassguideservice::unpack($row);
                $result=$species['state']==='archived'?['name'=>$species['scientific_name'],'fields'=>[],'sources'=>[],'media'=>[],'matched'=>false]:$apis->fetch($provider,$species);
                $store->applyEnrichment($job,$result);
                return ['selected'=>true,'provider'=>$provider,'species'=>$species['scientific_name'],'matched'=>$result['matched'],'fields'=>count($result['fields']),'media'=>count($result['media'])];
            } catch(GrassguideProviderException $e) { $store->failEnrichment($job,$e->status,$e->retryAfter); return ['selected'=>true,'provider'=>$provider,'error'=>'HTTP '.$e->status]; }
            catch(Throwable $e) { $store->failEnrichment($job,0,0,'Import failed: '.get_class($e));error_log('Grass enrichment: '.get_class($e));return ['selected'=>true,'provider'=>$provider,'error'=>'Import failed']; }
        },0);
    }
}
