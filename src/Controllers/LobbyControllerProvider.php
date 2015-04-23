<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


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
               'list' => $this->getGamesList($app['user']->getId()) ,
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
            $game_id = (int)$game_id ;
            $app['session']->set('game_id', $game_id);
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            } elseif ($result[0]->gameStarted()) {
                return $app->redirect($app['BASE_URL'].'/Setup/'.$game_id) ;
            } else {
                return $app['twig']->render('Lobby/Join.twig', array(
                   'layout_template' => 'layout.twig' ,
                   'game' => $result[0] ,
                ));
            }
        })
        ->bind('JoinGame');

        /*
         * Play game
         */
        $controllers->get('/Play/{game_id}', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $app['session']->set('game_id', $game_id);
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            } elseif ($result[0]->gameStarted()) {
                return $app->redirect($app['BASE_URL'].'/'.$result[0]->getPhase().'/'.$game_id) ;
            } else {
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
        })
        ->bind('PlayGame');

        /*
         * Save game
         */
        $controllers->get('/Save/{game_id}', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $app['session']->set('game_id', $game_id);
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            } else {
                return $app['twig']->render('Lobby/GameData.twig', array(
                   'layout_template' => 'layout.twig' ,
                   'gameData' => $result[0]->saveData() ,
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
            try {
                $this->joinGame($game_id , $app['user']->getId() , $app['user']->getDisplayName() , $partyName) ;
                $app['session']->getFlashBag()->add('success', sprintf(_('You joined and named your party %s') , $partyName ));
                return $app->json( $partyName , 201);
            } catch (\Exception $e) {
                $app['session']->getFlashBag()->add('error', $e->getMessage());
                return $app->json( $e->getMessage() , 201);
            }
        })
        ->bind('JoinGameAction');

        /*
        * POST target
        * Verb : Ready
        * Data : None
        */
        $controllers->post('/Join/{game_id}/Ready', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            } else {
                if ($result[0]->setPartyToReady($app['user']->getId()) ) {
                    $this->entityManager->persist($result[0]);
                    $this->entityManager->flush();
                    $app['session']->getFlashBag()->add('success', _('You are ready to start'));
                    return $app->json( _('You are ready to start') , 201);
                } else {
                    $app['session']->getFlashBag()->add('danger', _('Error - Invalid user.') );
                    return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
                }
            }
        })
        ->bind('ReadyAction');

        /*
        * POST target
        * Verb : Create
        * JSON Data : Game info
        */
        $controllers->post('/Create/Create', function(Request $request) use ($app)
        {
            $result= $this->CreateGame($request->request->all()) ;
            if ( $result === TRUE ) {
                $app['session']->getFlashBag()->add('success', 'Game created');
                return $app->json( _('Game created') , 201);
            } else {
                $app['session']->getFlashBag()->add('danger', $result);
                return $app->json( $result , 201);
            }
        })
        ->bind('CreateAction');

        return $controllers ;
    }
    
    /**
     * Returns a list of all Games in an array. Includes a 'canJoin' flag based on $user_id
     * @param int $user_id
     * @return array
     */
    public function getGamesList($user_id)
    {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g');
        $result = $query->getResult() ;
        return $result ;
    }

    private function joinGame($game_id , $user_id , $userName , $partyName) {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.$game_id);
        $game = $query->getResult() ;
        try {
            $party = new \Entities\Party($user_id , $userName, $partyName) ;
            $party->joinGame($game[0]) ;
            $this->entityManager->persist($game[0]);
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage()) ;
        }
    }
    
    private function CreateGame($data) {
        $data['gameName'] = strip_tags($data['gameName']) ;
        $query = $this->entityManager->createQuery('SELECT COUNT(g.id) FROM Entities\Game g WHERE g.name = ?1');
        $query->setParameter(1, $data['gameName']);
        if ($query->getSingleScalarResult() > 0) {
            return _('ERROR - A game with the same name already exists.') ;
        }
        if (strlen($data['gameName']) <1 ) {
            return _('ERROR - Game name too short.') ;
        }
        if (!in_array($data['scenario'], \Entities\Game::$VALID_SCENARIOS) ) {
            return sprintf(_('ERROR - %1$s is not a valid Scenario.') , $data['scenario']) ;
        }
        if (isset($data['variants'])) {
            foreach((array)$data['variants'] as $variant) {
                if (!in_array($variant , \Entities\Game::$VALID_VARIANTS)) {
                    return sprintf(_('ERROR - %1$s is not a valid Variant.') , $variant) ;
                }
            }
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
        } catch (Exception $e) {
            return _('Error') . $e->getMessage() ;
        }
    }

}