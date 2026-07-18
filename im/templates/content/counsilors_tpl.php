<?php

$objDBIMUser = $this->getObject('dbimusers', 'im');
$objLink = $this->getObject('link','htmlelements');
$numCounsilors = (is_countable($users) ? count($users) : 0);
$numUsers = $this->objDbImPres->getRecordCount();

echo "Number of Counsilors: $numCounsilors<br/>";
echo "Number of Users: $numUsers <br/><br/>";
foreach ($users as $user)
{
    $cnt = 0;
    $name = $user['firstname']." ".$user['surname'];
    if($objDBIMUser->isCounsilor($user['userid']))
    {
        $objLink->href = $this->uri(array("action" => "removecounsilor", "userid" => $user['userid']));
        $objLink->link = "remove";
        $cnt = count($this->objDbImPres->getUsers($user['userid']));
    }else{
        $objLink->href = $this->uri(array("action" => "addcounsilor", "userid" => $user['userid']));
        $objLink->link = "add";
    }

    echo $name."   ".$objLink->show().'  ('.$cnt.' users assigned) <br/>';

}


?>