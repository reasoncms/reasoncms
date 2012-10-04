<?php
    // header('Content-type: application/json');

/*
    id_10p1JJ792    thursday
    id_2i4d1Ls_41   friday630
    id_0A0E3h183v   friday915
    id_k383n777kr   saturday
    id_6hOG5v79TJ   sunday
*/


    $pdo=new PDO("mysql:dbname=thor;host=localhost","reason_user","8shtKGFGw4.v7cMm");
    $statement=$pdo->prepare("SELECT SUM(id_10p1JJ792) as [thursday], SUM(id_2i4d1Ls_41) as friday630, SUM(id_0A0E3h183v) as friday915, SUM(id_k383n777kr) as saturday, SUM(id_6hOG5v79TJ) as sunday FROM form_414584 ");
    $statement->execute();
    $results=$statement->fetchAll(PDO::FETCH_ASSOC);
    $json=json_encode($results);
    echo $json;
?>