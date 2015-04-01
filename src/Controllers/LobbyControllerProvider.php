<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

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
            return $app['twig']->render('Lobby/Create.twig', array(
               'layout_template' => 'layout.twig' ,
               'is_admin' => in_array('ROLE_ADMIN', $app['user']->getRoles()) ,
            ));
        })
        ->bind('CreateGame');

        /*
         * Join existing game
         */
        $controllers->get('/Join/{game_id}', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect('/Lobby/List') ;
            } elseif ($result[0]->gameStarted()) {
                return $app->redirect('/Setup/'.$game_id) ;
            } else {
                $gameInfos = $result[0]->getGameInfos($app['user']->getId()) ;
                return $app['twig']->render('Lobby/Join.twig', array(
                   'layout_template' => 'layout.twig' ,
                   'game' => $gameInfos['game'] ,
                   'parties' => $gameInfos['parties'] ,
                   'state' => $gameInfos['state'] ,
                ));
            }
        })
        ->bind('JoinGame');

        /*
        * POST target
        * Verb : Join
        * JSON data : "partyName"
        */
        $controllers->post('/Join/{game_id}/JoinGame', function($game_id , Request $request) use ($app)
        {
            $partyName = $request->request->get('partyName') ;
            try {
                $this->joinGame($game_id , $app['user']->getId() , $partyName) ;
                $app['session']->getFlashBag()->add('alert', sprintf(_('You joined and named your party %s') , $partyName ));
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
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect('/Lobby/List') ;
            } else {
                if ($result[0]->setPartyToReady($app['user']->getId()) ) {
                    $this->entityManager->flush();
                    $app['session']->getFlashBag()->add('alert', _('You are ready to start'));
                    return $app->json( _('You are ready to start') , 201);
                } else {
                    $app['session']->getFlashBag()->add('alert', _('Error - Invalid user.') );
                    return $app->redirect('/Lobby/List') ;
                }
            }
        })
        ->bind('ReadyAction');

        return $controllers ;
    }
    
    /**
     * Returns a list of all Games in an array. Includes a 'canJoin' flag based on $user_id
     * @param int $user_id
     * @return array
     */
    public function getGamesList($user_id)
    {
        $query = $this->entityManager->createQuery('SELECT g , p FROM Entities\Game g LEFT JOIN g.parties p');
        $result = $query->getArrayResult() ;
        foreach($result as $key=>$game) {
            $parties = $game['parties'] ;
            $result[$key]['state']='';
            foreach($parties as $party) {
                if ($party['user_id']==$user_id) {
                    $result[$key]['state'] = ($party['readyToStart'] ? 'READY' : 'JOINED' ) ;
                }
            }
            if (count($parties) == \Entities\Game::$MAX_PLAYERS) {
                $result[$key]['state'] = 'FULL' ;
            } elseif ($result[$key]['state']=='') {
                $result[$key]['state'] = 'CAN_JOIN' ;
            }
        }
        return $result ;
    }

    private function joinGame($game_id , $user_id , $partyName) {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.$game_id);
        $game = $query->getResult() ;
        try {
            $party = new \Entities\Party($user_id , $partyName) ;
            $party->joinGame($game[0]) ;
            $this->entityManager->persist($game[0]);
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage()) ;
        }
    }
    
}