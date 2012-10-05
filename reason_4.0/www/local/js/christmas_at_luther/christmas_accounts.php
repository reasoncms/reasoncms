<?php
    $account = htmlspecialchars($_GET["account"]);
    $first = htmlspecialchars($_GET["first"]);
    $last = htmlspecialchars($_GET["last"]);
    
    try{
        $pdo = new PDO("mysql:dbname=tix_for_reason;host=database-1.luther.edu","reason_user","8shtKGFGw4.v7cMm", array(PDO::ATTR_PERSISTENT => true));
        $statement = $pdo->prepare("SELECT first, last, address, city, state, zip, phone, email, cellphone FROM customer where account=" . $account . " and (last ='" . $last . "' or first='" . $first . "')");
        $statement->execute();
        $results=$statement->fetchAll(PDO::FETCH_ASSOC);
        $json=json_encode($results);
        echo $json;
        $pdo = null;
    } catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
    }
?>