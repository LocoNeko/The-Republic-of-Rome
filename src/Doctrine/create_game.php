<?php
// create_product.php
require_once "ORMbootstrap.php";

$game = new Entities\Game();

try 
{
    $game->setName('Test game 03') ;
    $game->setTreasury(100) ;
    $game->setUnrest(0) ;
    $game->setScenario('EarlyRepublic') ;
    createFromFile($game , 'EarlyRepublic') ;
    $entityManager->persist($game);
    $entityManager->flush();
    echo "Created Game with ID " . $game->getId() . "\n";
} catch (Exception $e) {
    echo "Error - exception: ", $e->getMessage(), "\n" ;
}

/**
 * Creates a deck from a scenario csv file located in <b>/../../data/scenarios/</b>
 * @param string $scenarioName The name of the scenario
 * @throws Exception
 */
function createFromFile($game , $scenarioName) {
    global $entityManager ;
    
    $filePointer = fopen(dirname(__FILE__).'/../../resources/scenarios/'.$scenarioName.'.csv', 'r');
    if (!$filePointer) {
        throw new Exception(_('Could not open the file'));
    }
    while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
        if ($data[0]!='') {
            $type = "Faction" ;
            if (Entities\Card::isValidType($type)) {
                $card = new Entities\Card ($game , $data[0] , $data[1] , $data[2]) ;
                $card->setLocation('Early Republic') ;
                $entityManager->persist($card);
                $game->getCards()->add($card) ;
            } else {
                throw new Exception(sprintf(_('Invalid card type %1$s') , $type));
            }
        }
    }
    fclose($filePointer);
}


