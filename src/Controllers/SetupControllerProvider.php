<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SetupControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;

        /*
         * Setup
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $game_id = (int)$game_id ;
            $app['session']->set('game_id', $game_id);
            $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
            $result = $query->getResult() ;
            if (count($result)!=1) {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->redirect('/') ;
            } elseif(!$result[0]->gameStarted()) {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not started.') , $game_id ));
                return $app->redirect('/') ;
            } else {
                return $app['twig']->render('Setup/Main.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $result[0]
                ));
            }
        })
        ->bind('Setup');
        
        /*
        * POST target
        * Verb : PickLeader
        * JSON data : "dropOn" => a card id
        */
        $controllers->post('/{game_id}/PickLeader', function($game_id , Request $request) use ($app)
        {
            $game = $this->getGame((int)$game_id) ;
            if ($game!==FALSE) {
                try {
                    // Find the Senator on which the pick leader verb was dropped
                    $leader = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('id', $request->request->get('dropOn')) ;
                    if ($leader!=FALSE) {
                        // Find the user_id and the location of the Senator
                        $user_id = (int)$app['user']->getId() ;
                        $location = $leader->getLocation() ;
                        if ($location['type'] == 'party' && $user_id == $location['value']->getUser_id()) {
                            $party = $location['value'] ;
                            $party->setLeader($leader) ;
                            $game->log( _('%1$s is appointed leader of %2$s').' ([['.$user_id.']])','log',array($leader->getName() , $party->getName()) );
                            // If veryone has picked a leader, move to next phase
                            $finished = TRUE ;
                            foreach ($game->getParties() as $aParty) {
                                if ($aParty->getLeader() == NULL) {
                                    $finished = FALSE ;
                                }
                            }
                            if ($finished) {
                                $game->setSubPhase('Play cards') ;
                                $game->resetAllIsDone() ;
                            }
                            $this->entityManager->persist($game);
                            $this->entityManager->flush();
                            return $app->json( 'SUCCESS' , 201);
                        } else {
                            $app['session']->getFlashBag()->add('error', _('ERROR - Wrong user or Senator not in a party'));
                            return $app->json( _('ERROR - Wrong user or Senator not in a party') , 201);
                        }
                    } else {
                        // The Senator was not found
                        $app['session']->getFlashBag()->add('error', _('ERROR - Senator not found'));
                        return $app->json( _('ERROR - Senator not found') , 201);
                    }
                } catch (\Exception $e) {
                    $app['session']->getFlashBag()->add('error', $e->getMessage());
                    return $app->json( $e->getMessage() , 201);
                }
            } else {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_PickLeader');

        /*
        * POST target
        * Verb : DonePlayingCards
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/DonePlayingCards', function($game_id , Request $request) use ($app)
        {
            $game = $this->getGame((int)$game_id) ;
            if ($game!==FALSE) {
                $game->getParty($user_id)->setIsDone(TRUE) ;
                $game->log('[['.$user_id.']] '._('{are,is} done playing cards.')) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } else {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_DonePlayingCards');

        /*
        * POST target
        * Verb : Play Statesman
        * JSON data : card_id
        */
        $controllers->post('/{game_id}/Play Statesman', function($game_id , Request $request) use ($app)
        {
            $game = $this->getGame((int)$game_id) ;
            if ($game!==FALSE) {
                $user_id = (int)$app['user']->getId() ;
                $statesman = $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('card_id') , $game->getParty($user_id)->getSenators()) ;
                $game->log(_('[['.$user_id.']]'.' {play,plays} Statesman %1$s') , 'log' , array($statesman->getName()));
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } else {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('Play Statesman');

        /*
        * POST target
        * Verb : Play Statesman
        * JSON data : dragFrom => card_id , dropOn => card_id
        */
        $controllers->post('/{game_id}/Play Concession', function($game_id , Request $request) use ($app)
        {
            $game = $this->getGame((int)$game_id) ;
            if ($game!==FALSE) {
                $user_id = (int)$app['user']->getId() ;
                $recipient = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('id', $request->request->get('dropOn')) ;
                if ($recipient!=FALSE) {
                    $concession = $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('dragFrom')) ;
                    if($concession!=FALSE) {
                        error_log($concession->getName().' dropped on '.$recipient->getName() ) ;
                        $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('dragFrom') , $recipient->getCardsControlled()) ;
                        $game->log(_('[['.$user_id.']]'.' {play,plays} %1$s on %2$s.') , 'log' , array($concession->getName() , $recipient->getName()));
                        $this->entityManager->persist($game);
                        $this->entityManager->flush();
                        return $app->json( 'SUCCESS' , 201);
                    } else {
                        // The Concession was not found
                        $app['session']->getFlashBag()->add('error', _('ERROR - Concession not found'));
                        return $app->json( _('ERROR - Concession not found') , 201);
                    }
                } else {
                    // The Senator was not found
                    $app['session']->getFlashBag()->add('error', _('ERROR - Senator not found'));
                    return $app->json( _('ERROR - Senator not found') , 201);
                }
            } else {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('Play Concession');

        return $controllers ;
    }
    
    /**
     * Returns a Game entity corresponding to this $game_id, or FALSE if not found
     * @param int $game_id
     * @return Entity\Game | FALSE
     */
    function getGame($game_id) {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
        $result = $query->getResult() ;
        return ( (count($result)==1) ? $result[0] : FALSE ) ;
    }
    
}