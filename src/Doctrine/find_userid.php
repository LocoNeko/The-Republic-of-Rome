<?php
require_once "ORMbootstrap.php";

$id = $argv[1] ;
$user_id = $argv[2] ;

$game = $entityManager->find('Game', $id);
echo $game->userAlreadyJoined($user_id)."\n" ;
