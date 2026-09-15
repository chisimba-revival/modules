<?php
/** Stable imported-record comparison; source attribution is not account ownership. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class publishingimportrecord
{
    public const FIELDS=['id','blogid','userid','datecreated','modifierid','datemodified','post_title','post_content','post_status','post_type','post_tags','featured_image','featured_alt','composition_json','legacy_content_html','published_at','author_credit','source_key','source_url'];
    public static function fingerprint(array $row)
    { $values=[];foreach(self::FIELDS as $key)$values[$key]=(string)($row[$key]??'');return hash('sha256',json_encode($values,JSON_THROW_ON_ERROR)); }
    public static function compare($old,array $new)
    {
        if(!$old)return 'created';
        if(!hash_equals((string)$old['source_hash'],$new['source_hash'])||!hash_equals((string)$old['import_hash'],self::fingerprint($old))||!hash_equals((string)$old['import_hash'],self::fingerprint($new)))throw new RuntimeException('Imported article changed; review '.$new['source_key']);
        return 'unchanged';
    }
}
