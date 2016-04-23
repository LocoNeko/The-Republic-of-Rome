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
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $user_id = (int)$app['user']->getId() ;

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                $game->getParty($user_id)->setLastUpdateToNow() ;
                
                $view = new \Presenters\MortalityPhasePresenter($game , $user_id) ;

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
        ->bind('Mortality');
        
        /*
        * POST target
        * Verb : MortalityReady
        */
        $controllers->post('/{game_id}/MortalityReady', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $game->getParty($user_id)->setIsDone(TRUE) ;
                if ($game->isEveryoneDone())
                {
                    $this->doMortality($game) ;
                    $game->setPhase('Revenue') ;
                    $game->setSubPhase('Base') ;
                    $game->resetAllIsDone() ;
                    $game->revenue_init() ;
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
        ->bind('verb_MortalityReady');
    
        return $controllers ;
    }
   
    /**
     * Perform the actual mortality phase :
     * - Activate imminent wars
     * - Draw mortality chots and kill some senators (they deserve it)
     * @param \Entities\Game $game
     */
    function doMortality($game)
    {
        // First, handle Imminent Wars activation
        if ($game->getDeck('imminentWars')->getNumberOfCards() > 0)
        {
            $game->log(_('There is no imminent conflict to activate.')) ;
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


