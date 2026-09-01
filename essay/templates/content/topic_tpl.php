<?php
$ret ="";
if (!$objUser->isCourseAdmin($this->contextcode)) {
    $ret .= $content;
} else {
    $icons=$this->getObject('iconservice','ui');
    $url=htmlspecialchars($this->uri(array(),'essayadmin'),ENT_QUOTES,'UTF-8');
    $ret .= '<div class="chisimba-actions"><a class="button" href="'.$url.'">'
        .$icons->render('pencil',array('decorative'=>true)).' Manage essays</a></div>';
}

echo "<div class='essay_main'>$ret</div>";
?>
