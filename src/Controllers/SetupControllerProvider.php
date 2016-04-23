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
                $user_id = (int)$app['user']->getId() ;

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                $game->getParty($user_id)->setLastUpdateToNow() ;

                $view = new \Presenters\SetupPhasePresenter($game , $user_id) ;

                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'InGameLayout.twig' ,
                    'view' => $view
                ));
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->redirect('/') ;
            }
        })
        ->bind('Setup');
        
        /*
        * POST target
        * Verb : PickLeader
        */
        $controllers->post('/{game_id}/PickLeader', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $leader = $game->getFilteredCards(array('senatorID'=>$json_data['to']['senatorID']))->first() ;
                $party = $game->getParty($user_id) ;
                $party->setLeader($leader) ;
                $game->log( _('%1$s is appointed leader of %2$s').' ([['.$user_id.']])' , 'log' , array($leader->getName() , $party->getName()) );
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
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_PickLeader');

        /*
        * POST target
        * Verb : setupPlayStatesman
        */
        $controllers->post('/{game_id}/setupPlayStatesman', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $game->playStatesman($user_id , $json_data['senatorID']) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_setupPlayStatesman');

        /*
        * POST target
        * Verb : Play Concession
        * JSON data : dragFrom => card_id , dropOn => card_id
        */
        $controllers->post('/{game_id}/setupPlayConcession', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $recipient = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('senatorID', $json_data['to']['senatorID']) ;
                $concession = $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $json_data['from']['card_id']) ;
                $game->getParty($user_id)->getHand()->getFirstCardByProperty('id', $json_data['from']['card_id'] , $recipient->getCardsControlled()) ;
                $game->log(_('[['.$user_id.']]'.' {play,plays} %1$s on %2$s.') , 'log' , array($concession->getName() , $recipient->getName()));
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_setupPlayConcession');

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
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
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
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_DonePlayingCards');

        return $controllers ;
    }
}