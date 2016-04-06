<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class MortalityControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
        /*
         * Mortality
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', (int)$game_id);
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , (int)$game_id ));
                return $app->redirect('/') ;
            }
            elseif(!$game->gameStarted())
            {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not started.') , (int)$game_id ));
                return $app->redirect('/') ;
            }
            else
            {
                $gameView = new \Presenters\GamePresenter($game) ;
                $mortalityView = new \Presenters\MortalityPhasePresenter($game) ;
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $game,
                    'gameView' => $gameView ,
                    'mortalityView' => $mortalityView->getHeader((int)$app['user']->getId())
                ));
            }
        })
        ->bind('Mortality');
        
        /*
        * POST target
        * Verb : MortalityReady
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/MortalityReady', function($game_id , Request $request) use ($app)
        {
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
                $game->getParty($user_id)->setIsDone(TRUE) ;
                if ($game->isEveryoneDone())
                {
                    $this->doMortality($game) ;
                    $game->setPhase('Revenue') ;
                    $game->setSubPhase('Base') ;
                    $app['saveGame']($game) ;
                    $game->resetAllIsDone() ;
                    $game->revenue_init() ;
                }
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_MortalityReady');
    
        return $controllers ;
    }
   
    function doMortality($game)
    {
        // First, handle Imminent Wars activation
        if ($game->getDeck('imminentWars')->getNumberOfCards() > 0)
        {
            $game->log(_('There is no imminent conflicts to activate.')) ;
        }
        else
        {
            // Get imminent Wars in an array and sort it by card id
            $imminentWars = $game->getDeck('imminentWars')->getCards()->toArray() ;
            usort ($imminentWars , function ($a,$b)
            {
                return ($a->getId() < $b->getId()) ;
            });
            
            foreach ($imminentWars as $conflict)
            {
                // Activate the first imminent War
                $game->getDeck('imminentWars')->getFirstCardByProperty('id' , $conflict->getId() , $game->getDeck('activeWars')) ;
                $game->log(_('Imminent conflict %1$s has been activated.') , 'alert' , array($conflict->getName()) );
                    
                // Remove all other matching wars from $imminentWars
                $matchingName = $conflict->getMatches() ;
                foreach($imminentWars as $key => $matchingConflict)
                {
                    if ($matchingConflict->getMatches() == $matchingName)
                    {
                        unset($imminentWars[$key]) ;
                    }
                }
            }
        }
        
        // Second, draw mortality chits
        $chits = $game->mortality_chits(1) ;
        foreach ($chits as $chit)
        {
            if ($chit!='NONE' && $chit!='DRAW 2')
            {
                $returnedMessage= $game->killSenator((string)$chit) ;
                $game->log 
                (
                    _('Chit drawn : %1$s. %2$s') ,
                    (isset($returnedMessage[1]) ? $returnedMessage[1] : 'log') ,
                    array($chit , $returnedMessage[0])
                );
            }
            else
            {
                $game->log(_('Chit drawn : %1$s') , 'log' , array($chit)) ;
            }
        }

    }
    
}


