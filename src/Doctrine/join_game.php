<?php
require_once "ORMbootstrap.php";

$id = $argv[1] ;
$user_id = $argv[2] ;
$name = $argv[3] ;

$game = $entityManager->find('Entities\Game', $id);

try {
    $party = new Entities\Party($user_id , $name) ;
    $party->joinGame($game) ;
    $entityManager->persist($game);
    $entityManager->persist($party);
    $entityManager->flush();
} catch (Exception $e) {
    echo "Caught exception: ", $e->getMessage(), "\n" ;
}
