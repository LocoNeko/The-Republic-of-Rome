<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;


class LobbyControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;

        /*
         * List existing games
         */
        $controllers->get('/List', function() use ($app)
        {
            $app['session']->set('game_id', NULL);
            return $app['twig']->render('Lobby/List.twig', array(
               'layout_template' => 'layout.twig' ,
               'list' => $this->getGamesList() ,
               'savedGamesList' => $this->getSavedGamesList() ,
               'is_admin' => in_array('ROLE_ADMIN', $app['user']->getRoles()) ,
            ));
        })
        ->bind('ListGames');

        /*
         * Create game
         */
        $controllers->get('/Create', function() use ($app)
        {
            $app['session']->set('game_id', NULL);
            return $app['twig']->render('Lobby/Create.twig', array(
               'layout_template' => 'layout.twig' ,
               'is_admin' => in_array('ROLE_ADMIN', $app['user']->getRoles()) ,
               'scenarios' => \Entities\Game::$VALID_SCENARIOS ,
               'variants' => \Entities\Game::$VALID_VARIANTS ,
            ));
        })
        ->bind('CreateGame');

        /*
         * Join existing game
         */
        $controllers->get('/Join/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', $game_id);
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
            elseif ($game->gameStarted())
            {
                return $app->redirect($app['BASE_URL'].'/Setup/'.$game_id) ;
            }
            else
            {
                return $app['twig']->render('Lobby/Join.twig', array(
                   'layout_template' => 'layout.twig' ,
                   'game' => $game ,
                ));
            }
        })
        ->bind('JoinGame');

        /*
         * Play game
         */
        $controllers->get('/Play/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', $game_id);
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
            elseif ($game->gameStarted())
            {
                return $app->redirect($app['BASE_URL'].'/'.$game->getPhase().'/'.$game_id) ;
            }
            else
            {
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ; 
            }
        })
        ->bind('PlayGame');

        /*
         * Save game
         */
        $controllers->get('/SaveGame/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', $game_id);
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
            else
            {
                return $app['twig']->render('Lobby/GameData.twig', array(
                   'layout_template' => 'layout.twig' ,
                   'gameData' => $game->saveData() ,
                ));
            }
        })
        ->bind('SaveGame');

        /*
        * POST target
        * Verb : Join
        * JSON data : "partyName"
        */
        $controllers->post('/Join/{game_id}/JoinGame', function($game_id , Request $request) use ($app)
        {
            $partyName = $request->request->get('partyName') ;
            $game_id = (int)$game_id ;
            try
            {
                $this->joinGame($game_id , $app['user']->getId() , $app['user']->getDisplayName() , $partyName) ;
                $app['session']->getFlashBag()->add('success', sprintf(_('You joined and named your party %s') , $partyName ));
                return $app->json( $partyName , 201);
            }
            catch (\Exception $e)
            {
                $app['session']->getFlashBag()->add('danger', $e->getMessage());
                return $app->json( $e->getMessage() , 201);
            }
        })
        ->bind('verb_JoinGame');

        /*
        * POST target
        * Verb : Ready
        * Data : None
        */
        $controllers->post('/Join/{game_id}/Ready', function($game_id) use ($app)
        {
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
            else
            {
                if ($this->setPartyToReady($game, $app['user']->getId()) )
                {
                    // If the game was started, save it
                    if ($game->gameStarted())
                    {
                        $app['saveGame']($game) ;
                    }
                    $this->entityManager->persist($game);
                    $this->entityManager->flush();
                    $app['session']->getFlashBag()->add('success', _('You are ready to start'));
                    return $app->json( _('You are ready to start') , 201);
                }
                else
                {
                    $app['session']->getFlashBag()->add('danger', _('Error - Invalid user.') );
                    return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
                }
            }
        })
        ->bind('verb_Ready');

        /*
        * POST target
        * Verb : Create
        * JSON Data : Game info
        */
        $controllers->post('/Create/Create', function(Request $request) use ($app)
        {
            $result= $this->CreateGame($request->request->all()) ;
            if ( $result['error'] === FALSE)
            {
                $app['session']->set('game_just_created' , TRUE);
                $app['session']->set('game_id' , $result['gameId']);
                $app['session']->getFlashBag()->add('success', 'Game created');
                return $app->json( 'Game created' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', $result['message']);
                return $app->json( $result , 201);
            }
        })
        ->bind('verb_Create');

        /*
        * POST target
        * Verb : Load
        * JSON data : 
        */
        $controllers->post('/List/LoadGame', function(Request $request) use ($app)
        {
            try
            {
                $this->loadGame($request->request->all()) ;
                $app['session']->getFlashBag()->add('success', _('Game loaded'));
                return $app->json( 'Game loaded' , 201);
            }
            catch (\Exception $e)
            {
                $app['session']->getFlashBag()->add('error', $e->getMessage());
                return $app->json( $e->getMessage() , 201);
            }
        })
        ->bind('verb_LoadGame');

        return $controllers ;
    }
    
    /**
     * 
     * CONTROLLER'S FUNCTIONS
     * 
     */
    
    /**
     * Returns a list of all Games in an array. Includes a 'canJoin' flag based on $user_id
     * @return array
     */
    public function getGamesList()
    {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g');
        $result = $query->getResult() ;
        return $result ;
    }

    public function getSavedGamesList()
    {
        $query = $this->entityManager->createQuery('SELECT s FROM Entities\SavedGame s ORDER BY s.savedTime DESC');
        $result = array() ;
        foreach ($query->getResult() as $savedGame)
        {
            if (!isset($result[$savedGame->getGame_id()]))
            {
                $result[$savedGame->getGame_id()] = array() ;
            }
            array_push(
                $result[$savedGame->getGame_id()] ,
                array (
                    'savedGameId' => $savedGame->getSavedGameId() ,
                    'name' => $savedGame->getSavedTime()->format('Y-m-d H:i:s').', Turn '.$savedGame->getTurn().' - '.$savedGame->getPhase().($savedGame->getSubPhase()!='' ? ' - '.$savedGame->getSubPhase() : ''))
                );
        }
        return $result ;
    }

    private function joinGame($game_id , $user_id , $userName , $partyName) {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.$game_id);
        $game = $query->getResult() ;
        try
        {
            $party = new \Entities\Party($user_id , $userName, $partyName) ;
            $party->joinGame($game[0]) ;
            $this->entityManager->persist($game[0]);
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        }
        catch (Exception $e)
        {
            throw new \Exception($e->getMessage()) ;
        }
    }
    
    /**
     * 
     * @param type $data
     * @return array 'error' , 'message' , 'gameId'
     */
    private function CreateGame($data)
    {
        $result = array() ;
        $result['error'] = FALSE ;
        $data['gameName'] = strip_tags($data['gameName']) ;
        $query = $this->entityManager->createQuery('SELECT COUNT(g.id) FROM Entities\Game g WHERE g.name = ?1');
        $query->setParameter(1, $data['gameName']);
        if ($query->getSingleScalarResult() > 0)
        {
            $result['error'] = TRUE ;
            $result['message'] = _('ERROR - A game with the same name already exists.') ;
        }
        elseif (strlen($data['gameName']) <1 )
        {
            $result['error'] = TRUE ;
            $result['message'] = _('ERROR - Game name too short.') ;
        }
        elseif (!in_array($data['scenario'], \Entities\Game::$VALID_SCENARIOS) )
        {
            $result['error'] = TRUE ;
            $result['message'] = sprintf(_('ERROR - %1$s is not a valid Scenario.') , $data['scenario']) ;
        }
        elseif (isset($data['variants']))
        {
            foreach((array)$data['variants'] as $variant)
            {
                if (!in_array($variant , \Entities\Game::$VALID_VARIANTS))
                {
                    $result['error'] = TRUE ;
                    $result['message'] = sprintf(_('ERROR - %1$s is not a valid Variant.') , $variant) ;
                }
            }
        } else {
            $data['variants']=array();
        }
        
        $game = new \Entities\Game();
        try 
        {
            $game->setName($data['gameName']) ;
            $game->setTreasury(100) ;
            $game->setUnrest(0) ;
            $game->setScenario($data['scenario']) ;
            $game->setVariants($data['variants']) ;
            $game->log(_('Game "%1$s" created. Scenario : %2$s') , 'log' , array($data['gameName'] , $data['scenario']) ) ;
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            $result['error'] = FALSE ;
            $result['gameId'] = $game->getId() ;
        }
        catch (Exception $e)
        {
            $result['error'] = TRUE ;
            $result['message'] = _('Error') . $e->getMessage() ;
            
        }
        return $result ;
    }
    
    /**
     * Loads game data from savedGame into Game
     * @param type $data
     * @return boolean
     */
    private function loadGame($data)
    {
        $savedGameId = strip_tags($data['savedGameId']) ;
        $query = $this->entityManager->createQuery('SELECT s FROM Entities\SavedGame s WHERE s.savedGameId = '.$savedGameId);
        $result = $query->getResult() ;
        if (count($result)!=1)
        {
            return FALSE ;
        }
        else
        {
            // Determine the number of savedGames that were saved later (in order to delete them)
            $savedGame = $result[0] ;
            $query2 = $this->entityManager->createQuery('SELECT COUNT(s.savedGameId) FROM Entities\SavedGame s WHERE s.game_id= ?1 AND s.savedTime > ?2 ') ;
            $query2->setParameter(1 , $savedGame->getGame_id() ) ;
            $query2->setParameter(2 , $savedGame->getSavedTime()->format('Y-m-d H:i:s')) ;
            $count = $query2->getSingleScalarResult();
            error_log(sprintf(_('LOAD - Game %1$s (ID : %2$d) loaded. Turn %3$d - %4$s - %5$s') , $savedGame->getName() , $savedGame->getGame_id() , $savedGame->getTurn() , $savedGame->getPhase() , $savedGame->getSubPhase()) ) ;
            // Actual load function : Find the id of the game to be loaded, then load
            $query3 = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id= ?1') ;
            $query3->setParameter(1 , $savedGame->getGame_id() ) ;
            $result3 = $query3->getResult() ;
            $game = $result3[0] ;
            $game->loadData($savedGame->getGameData()) ;
        }
    }
    
    /**
     * Sets the party with $user_id in game $game to ready to start ($party->readyToStart=TRUE)
     * 
     * @param \Entities\Game $game
     * @param int $user_id
     * @return boolean
     */
    public function setPartyToReady($game , $user_id)
    {
        foreach($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id)
            {
                $party->setReadyToStart() ;
                if ($game->gameStarted())
                {
                    $this->doSetup($game) ;
                }
                return TRUE ;
            }
        }
        return FALSE ;
    }

    /**
     * Sets-up a game
     * @param \Entities\Game $game
     */
    public function doSetup($game)
    {
        $game->setPhase('Setup') ;
        $game->nextTurn() ;

        // Create all tables : Events, Appeal, Land bill, Population
        $this->createEvents($game) ;
        $this->createAppealTable($game) ;
        $this->createLandBillsTable($game) ;
        $this->createPopulationTable($game) ;
        
        // Early Republic deck
        $earlyRepublicDeck = $game->getDeck('earlyRepublic') ;
        $this->populateDeckFromFile($game , $game->getScenario() , 'earlyRepublic') ;

        // Unplayed provinces deck
        $this->populateDeckFromFile($game , 'Provinces' , 'unplayedProvinces') ;
        
        // Handle special cards : The First Punic war & Era ends
        $game->log(_('The First Punic War goes to the "Inactive" Wars deck.') , 'alert' ) ;
        $earlyRepublicDeck->getFirstCardByProperty('id' , 1 , $game->getDeck('inactiveWars')) ;
        $game->log(_('The "Era Ends" card goes to the discard. (MUST FIX)') , 'error' ) ;
        $earlyRepublicDeck->getFirstCardByProperty('id' , 65 , $game->getDeck('discard')) ;
        
        // Then create 4 legions in Rome, the rest of the legions and all the fleets are non-existent (Legions and Fleet objects should never be created during a game)
        for($i=1;$i<=25;$i++) 
        {
            $legion = new \Entities\Legion($game,$i) ;
            $game->getLegions()->add($legion) ;
            if ($i<=4) 
            {
                $legion->setOtherLocation('Rome') ;
            }
            $fleet = new \Entities\Fleet($game,$i) ;
            $game->getFleets()->add($fleet) ;
        }
        $game->log(_('Rome starts with 4 regular Legions.') ) ;
        
        // Give initial senators & cards to parties
        foreach ($game->getParties() as $party)
        {
            
            // Senators
            $senatorsList = '' ;
            for ($i=1 ; $i<=3 ; $i++)
            {
                $earlyRepublicDeck->shuffle() ;
                $card = $earlyRepublicDeck->getFirstCardByProperty('preciseType' , 'Senator' , $party->getSenators()) ;
                $senatorsList.=$card->getName() ;
                switch($i) {
                    case 1  : $senatorsList.= ' , '   ; break ;
                    case 2  : $senatorsList.= ' and ' ; break ;
                    default : $senatorsList.= '.'     ;
                }
            }
            $game->log(_('[['.$party->getUser_id().']] {receive,receives} the following Senators : %1$s') , 'log' , array($senatorsList) ) ;
            
            //Cards
            $cardsList = '' ;
            $cardsLeftToDraw = 3 ;
            while ($cardsLeftToDraw>0)
            {
                $earlyRepublicDeck->shuffle() ;
                $card = $earlyRepublicDeck->drawFirstCard() ;
                
                switch ($card->getPreciseType())
                {
                    case 'Faction card' :
                    case 'Statesman' :
                    case 'Concession' :
                        $party->getHand()->putCardOnTop($card);
                        $cardsList.= $card->getName().' , ' ;
                        $cardsLeftToDraw--;
                        break ;
                    default :
                        $earlyRepublicDeck->putCardOnTop($card);
                }
            }
            $game->log(_('[['.$party->getUser_id().']] receives three cards') , 'log' , NULL , $game->getAllPartiesButOne($party->getUser_id()) ) ;
            $game->log(_('You receive the following cards in hand : %1$s') , 'log' , array($cardsList) , new ArrayCollection(array($party)) ) ;
        }
        // Temporary Rome Consul
        try
        {
            $alignedSenators = $game->getAllSenators('alignedInRome')->toArray() ;
            $temporaryRomeConsul = $alignedSenators[array_rand($alignedSenators)] ;
            $temporaryRomeConsul->appoint('Rome Consul') ;
            $temporaryRomeConsul->setPriorConsul(TRUE) ;
            $game->log(_('%1$s is appointed temporary Rome Consul') , 'log' , array($temporaryRomeConsul->getName())) ;
            do
            {
                $game->getDeck('drawDeck')->putCardOnTop($game->getDeck('earlyRepublic')->drawFirstCard()) ;
            }
            while ($game->getDeck('earlyRepublic')->getNumberOfCards()>0) ;
            $game->getDeck('drawDeck')->shuffle();
        }
        catch (Exception $e)
        {
            $game->log($e->getMessage() , 'error') ;
        }
        $game->setSubPhase('PickLeaders') ;
    }
    
    /*
     * Convenience function (could be inside doSetup)
     * The event file should have 4 columns :
     * Event number (should be VG card number) ; event name ; increased event name ; description ; increased event description ; maximum level of the event (0 if none)
     * The event table file should have 3 columns :
     * event number for Early Republic ; Middle Republic ; Late Republic 
     * @param \Entities\Game\ $game
     */
    public function createEvents($game) {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/events.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the events file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $game->setEvent(
                $data[0] ,
                array(
                    'name' => $data[1] ,
                    'increased_name' => $data[2] ,
                    'description' => $data[3] ,
                    'increased_description' => $data[4] ,
                    'max_level' => $data[5] ,
                    'level' => 0
                )
            ) ;
        }
        fclose($filePointer);
        $filePointer2 = fopen(dirname(__FILE__).'/../../resources/tables/eventTable.csv', 'r');
        if (!$filePointer2) {
            throw new Exception(_('Could not open the event table file'));
        }
        $i=3;
        while (($data = fgetcsv($filePointer2, 0, ";")) !== FALSE) {
            $game->setEventTable($i++ , $data) ;
        }
        fclose($filePointer2);
    }

    /**
     * Reads the appealTable csv file and creates an array appealTable : keys = roll , values = array ('votes' => +/- votes , 'special' => NULL|'killed'|'freed' )
     * @param \Entities\Game $game
     * @throws Exception
     */
    public function createAppealTable($game) {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/appealTable.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Appeal table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $game->setAppealTable($data) ;
        }
        fclose($filePointer);
    }

    /**
     * Reads the landBills csv file and creates the landBillsTable array
     * @param \Entities\Game $game
     * @throws Exception
     */
    public function createLandBillsTable($game) {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/landBills.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Land Bills table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            if (substr($data[0],0,1)!='#') {
                $game->setLandBillsTable($data);
            }
        }
        fclose($filePointer);
    }

    /**
     * Reads the populationTable csv file and creates an array Unrest level => array of effects
     * Effects are : +# increase unrest by # , -# decrease unrest by # , MS manpower shortage , NR no recruitment , Mob
     * @param \Entities\Game $game
     * @throws Exception
     */
    public function createPopulationTable($game) {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/populationTable.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Population table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $game->populationTable[$data[0]] = array();
            $effects = explode(',', $data[1]);
            foreach($effects as $effect) {
                array_push($game->populationTable[$data[0]] , $effect);
            }
        }
        fclose($filePointer);
    }

    /**
     * Looks for a file in resources/scenarios and creates a Deck based on it, by creating Cards as long as they have a valid type
     * @param \Entities\Game $game
     * @param string $fileName The file name for this scenario
     * @param string $deckName The deck to populate (since cards exist for Provinces as well)
     * @throws Exception When couldn't open file
     */
    public function populateDeckFromFile($game , $fileName , $deckName)
    {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/scenarios/'.$fileName.'.csv', 'r');
        if (!$filePointer)
        {
            throw new Exception(_('Could not open the file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE)
        {
            if ($data[0]!='')
            {
                $type = $data[2] ;
                if (\Entities\Card::isValidType($type))
                {
                    $class = 'Entities\\'.$type ;
                    $card = new $class ($data);
                    $game->getDeck($deckName)->putCardOnTop($card) ;
                }
            }
        }
        fclose($filePointer);
    }
    

}