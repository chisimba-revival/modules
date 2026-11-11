<?php
/** Pure validation and identification rules. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class practicerules extends ChisimbaObject
{
    public static function normalise(string $answer): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', str_replace(['’','‘'], "'", $answer))), 'UTF-8');
    }
    public static function aliases(string $preferred, string $alternatives): array
    {
        $names = array_merge([$preferred], preg_split('/\R/u', $alternatives));
        $names = array_values(array_unique(array_filter(array_map([self::class,'normalise'], $names), fn($v)=>$v!=='')));
        if (!$names || count($names)>30) throw new DomainException('invalid');
        foreach ($names as $name) if (mb_strlen($name)>160) throw new DomainException('invalid');
        return $names;
    }
    public static function matches(string $answer, array $aliases): bool
    {
        return in_array(self::normalise($answer), $aliases, true);
    }
    public static function select(array $items, int $count): array
    {
        if ($count<1 || $count>50 || count($items)<$count) throw new DomainException('coverage');
        // Fisher-Yates with a system random source; a run stores this exact order.
        for ($i=count($items)-1; $i>0; --$i) { $j=random_int(0,$i); [$items[$i],$items[$j]]=[$items[$j],$items[$i]]; }
        return array_slice($items,0,$count);
    }
}
