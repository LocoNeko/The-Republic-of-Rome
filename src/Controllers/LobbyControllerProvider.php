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
                if ($game->setPartyToReady($app['user']->getId()) )
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
            if ( $result === TRUE)
            {
                $app['session']->getFlashBag()->add('success', 'Game created');
                return $app->json( 'Game created' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', $result);
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
    
    private function CreateGame($data)
    {
        $data['gameName'] = strip_tags($data['gameName']) ;
        $query = $this->entityManager->createQuery('SELECT COUNT(g.id) FROM Entities\Game g WHERE g.name = ?1');
        $query->setParameter(1, $data['gameName']);
        if ($query->getSingleScalarResult() > 0)
        {
            return _('ERROR - A game with the same name already exists.') ;
        }
        
        if (strlen($data['gameName']) <1 )
        {
            return _('ERROR - Game name too short.') ;
        }
        
        if (!in_array($data['scenario'], \Entities\Game::$VALID_SCENARIOS) )
        {
            return sprintf(_('ERROR - %1$s is not a valid Scenario.') , $data['scenario']) ;
        }
        
        if (isset($data['variants']))
        {
            foreach((array)$data['variants'] as $variant)
            {
                if (!in_array($variant , \Entities\Game::$VALID_VARIANTS))
                {
                    return sprintf(_('ERROR - %1$s is not a valid Variant.') , $variant) ;
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
            return TRUE ;
        }
        catch (Exception $e)
        {
            return _('Error') . $e->getMessage() ;
        }
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

}