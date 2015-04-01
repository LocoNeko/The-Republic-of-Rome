<?php
require_once "ORMbootstrap.php";
$id = $argv[1];

$cards = $entityManager->getRepository('Entities\Card')->findBy(array('game' => $id));

foreach ($cards as $card) {
    echo sprintf("-%s\n", $card->getName());
}