<?php
require_once "ORMbootstrap.php";

$id = $argv[1];
$value = $argv[2];

$game = $entityManager->find('Entities\Game', $id);

if ($game === null) {
    echo "Game $id does not exist.\n";
    exit(1);
}

try {
    $game->addVariant($value);
} catch (\Exception $e) {
    echo "Caught exception: ", $e->getMessage(), "\n" ;
}

$entityManager->flush();