<?php
require_once "ORMbootstrap.php";

$id = $argv[1];
$type = $argv[2];
$value = $argv[3];
$game = $entityManager->find('Game', $id);

if ($game === null) {
    echo "Game $id does not exist.\n";
    exit(1);
}

echo findCard($game , $type , $value)."\n";

function findCard($game , $property , $value) {
    $result = $game->getCards()->filter(
        function($card) use ($property , $value) {
            try {
                return $card->getValue($property) == $value ;
            } catch (Exception $e) {
                echo "Caught exception: ", $e->getMessage(), "\n" ;
            }
        }
    );
    return $result->count();
}