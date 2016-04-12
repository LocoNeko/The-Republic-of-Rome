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
            $app['session']->set('game_id', (int)$game_id);
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id , TRUE , array('PickLeaders' , 'PlayCards')) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->redirect('/') ;
            }
            $gameView = new \Presenters\GamePresenter($game) ;
            $setupView = new \Presenters\SetupPhasePresenter($game) ;
            return $app['twig']->render('BoardElements/Main.twig', array(
                'layout_template' => 'layout.twig' ,
                'game' => $game ,
                'gameView' => $gameView ,
                'setupView' => $setupView->getHeader((int)$app['user']->getId())
            ));
        })
        ->bind('Setup');
        
        /*
        * POST target
        * Verb : PickLeader
        * JSON data : "dropOn" => a card id
        */
        $controllers->post('/{game_id}/PickLeader', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            try
            {
                // Find the Senator on which the pick leader verb was dropped
                $user_id = (int)$app['user']->getId() ;
                $leader = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('id', $request->request->get('dropOn')) ;
                if ($leader!=FALSE)
                {
                    // Find the user_id and the location of the Senator
                    $location = $leader->getLocation() ;
                    if ($location['type'] == 'party' && $user_id == $location['value']->getUser_id())
                    {
                        $party = $location['value'] ;
                        $party->setLeader($leader) ;
                        $game->log( _('%1$s is appointed leader of %2$s').' ([['.$user_id.']])','log',array($leader->getName() , $party->getName()) );

                        // If everyone has picked a leader, move to next phase
                        $finished = TRUE ;
                        foreach ($game->getParties() as $aParty)
                        {
                            if ($aParty->getLeader() == NULL)
                            {
                                $finished = FALSE ;
                            }
                        }
                        if ($finished)
                        {
                            $game->setSubPhase('PlayCards') ;
                            $game->resetAllIsDone() ;
                        }
                        $this->entityManager->persist($game);
                        $this->entityManager->flush();
                        return $app->json( 'SUCCESS' , 201);
                    }
                    else
                    {
                        $app['session']->getFlashBag()->add('error', _('ERROR - Wrong user or Senator not in a party'));
                        return $app->json( _('ERROR - Wrong user or Senator not in a party') , 201);
                    }
                }
                else
                {
                    // The Senator was not found
                    $app['session']->getFlashBag()->add('error', _('ERROR - Senator not found'));
                    return $app->json( _('ERROR - Senator not found') , 201);
                }
            }
            catch (\Exception $e)
            {
                $app['session']->getFlashBag()->add('error', $e->getMessage());
                return $app->json( $e->getMessage() , 201);
            }
            
            
        })
        ->bind('verb_PickLeader');

        /*
        * POST target
        * Verb : Play Statesman
        * JSON data : card_id
        */
        $controllers->post('/{game_id}/Play Statesman', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            $statesmanId = $request->request->get('card_id') ;
            $statesman = $game->playStatesman($user_id , $statesmanId ) ;
            //$statesman = $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('card_id') , $game->getParty($user_id)->getSenators()) ;
            if ($statesman!==FALSE)
            {
                $this->entityManager->persist($game);
                $this->entityManager->flush();
            }
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_PlayStatesman');

        /*
        * POST target
        * Verb : Play Concession
        * JSON data : dragFrom => card_id , dropOn => card_id
        */
        $controllers->post('/{game_id}/Play Concession', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            $recipient = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('id', $request->request->get('dropOn')) ;
            if ($recipient!=FALSE)
            {
                $concession = $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('dragFrom')) ;
                if($concession!=FALSE)
                {
                    $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $request->request->get('dragFrom') , $recipient->getCardsControlled()) ;
                    $game->log(_('[['.$user_id.']]'.' {play,plays} %1$s on %2$s.') , 'log' , array($concession->getName() , $recipient->getName()));
                    $this->entityManager->persist($game);
                    $this->entityManager->flush();
                    return $app->json( 'SUCCESS' , 201);
                }
                else
                {
                    // The Concession was not found
                    $app['session']->getFlashBag()->add('error', _('ERROR - Concession not found'));
                    return $app->json( _('ERROR - Concession not found') , 201);
                }
            }
            else
            {
                // The Senator was not found
                $app['session']->getFlashBag()->add('error', _('ERROR - Senator not found'));
                return $app->json( _('ERROR - Senator not found') , 201);
            }
        })
        ->bind('verb_PlayConcession');

        /*
        * POST target
        * Verb : DonePlayingCards
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/DonePlayingCards', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            $game->getParty($user_id)->setIsDone(TRUE) ;
            $game->log('[['.$user_id.']] '._('{are,is} done playing cards.')) ;
            if ($game->isEveryoneDone())
            {
                $game->log(_('Everyone is done playing cards.'));
                $game->setPhase('Mortality') ;
                $app['saveGame']($game) ;
                $game->resetAllIsDone() ;
            }
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_DonePlayingCards');

        return $controllers ;
    }
}