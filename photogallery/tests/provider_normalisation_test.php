<?php
/** Pure provider fixtures: no HTTP calls, credentials or provider quota. */
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {}
require dirname(__DIR__) . '/classes/photoprovider_class_inc.php';
function verifyPhoto($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$photo = ['id'=>12, 'photographer_url'=>'https://www.pexels.com/@example/',
    'url'=>'https://www.pexels.com/photo/bird-12/', 'alt'=>'<b>Bird</b>',
    'photographer'=>'<i>Example</i>', 'src'=>['large'=>'https://images.pexels.com/photos/12/bird.jpg']];
$items = photoprovider::normalise('pexels', [$photo, $photo], 'https://www.pexels.com/@example');
verifyPhoto(count($items) === 1 && $items[0]['title'] === 'Bird' && $items[0]['credit'] === 'Example', 'Normalisation and duplicate removal');
verifyPhoto(photoprovider::normalise('pexels', [$photo], 'https://www.pexels.com/@example-other') === [], 'Exact owner matching');
foreach (['javascript:alert(1)', 'http://images.pexels.com/a.jpg', 'https://images.pexels.com.evil.test/a.jpg', 'https://user:pass@images.pexels.com/a.jpg'] as $url) {
    $bad = $photo; $bad['src']['large'] = $url;
    verifyPhoto(photoprovider::normalise('pexels', [$bad]) === [], 'Unsafe media URL rejected');
}
$flickr = ['link'=>'https://www.flickr.com/photos/example/123/', 'title'=>'Bird',
    'media'=>['m'=>'https://live.staticflickr.com/1/bird_m.jpg'], 'author'=>'name ("Example")', 'author_id'=>'123@N01'];
$item = photoprovider::normalise('flickr', [$flickr])[0];
verifyPhoto($item['image'] === 'https://live.staticflickr.com/1/bird_z.jpg' && $item['credit'] === 'Example', 'Flickr source attribution and derivative');
echo "PASS photo provider normalisation, owner matching, duplicate removal, unsafe URLs and attribution\n";
