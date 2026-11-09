<?php
require __DIR__.'/composition_test.php';
$v=$s->emptyBlock('video');$v['url']='https://www.youtube.com/shorts/dQw4w9WgXcQ';
check($v['video_width']==='normal','New videos default to normal width');
foreach(['portrait','landscape'] as $orientation)foreach(['normal','wide'] as $width){
 $v['video_orientation']=$orientation;$v['video_width']=$width;$v['video_transcript']="First line\n<script>literal</script>";
 $saved=$s->validate([$v])[0];$html=$s->render([$saved]);
 check(str_contains($html,'chisimba-video-frame--'.$orientation.' chisimba-video-frame--'.$width),'Orientation and width render independently');
 check(str_contains($html,'&lt;script&gt;literal&lt;/script&gt;')&&str_contains($html,'<details>'),'Transcript is escaped and expandable');
 check(str_contains($html,'youtube-nocookie.com/embed/dQw4w9WgXcQ'),'Shorts player supported');
 $v['url']='/media/vertical.mp4';check(str_contains($s->render([$v]),'<video'),'Same controls for direct videos');$v['url']='https://youtu.be/dQw4w9WgXcQ';
}
$old=$v;unset($old['video_width'],$old['video_orientation'],$old['video_transcript']);$old=$s->validate([$old])[0];check($old['video_width']==='wide'&&$old['video_orientation']==='landscape','Existing horizontal videos keep layout');
$old['url']='https://www.tiktok.com/@example/video/1234567890123456789';unset($old['video_orientation']);check($s->validate([$old])[0]['video_orientation']==='portrait','Existing TikTok portrait retained');
$v['video_orientation']='square';rejects(fn()=>$s->validate([$v]));$v['video_orientation']='portrait';$v['video_width']='bad';rejects(fn()=>$s->validate([$v]));
echo "PASS page/post video orientation, widths, Shorts, direct video, transcript safety and legacy defaults.\n";
$registration=file_get_contents(__DIR__.'/../register.conf');
foreach(['videoorientation','landscape','portrait','video_display_width','video_width_normal','video_width_wide','videotranscript','transcript','video_controls_help'] as $key)check(str_contains($registration,'TEXT: mod_contentblocks_comp_'.$key.'|'),'Registered composition label: '.$key);
echo "PASS all video controls and Help use registered composition language keys.\n";
$v=$s->emptyBlock('video');$v['url']='https://youtu.be/dQw4w9WgXcQ';
check(!str_contains($s->render([$v]),'mod_contentblocks_comp_open_video'),'YouTube already supplies its own source link');
$v['url']='https://vimeo.com/123456789';check(str_contains($s->render([$v]),'mod_contentblocks_comp_open_video'),'Other embed providers retain source fallback');
echo "PASS YouTube source-link suppression and other-provider fallback.\n";
