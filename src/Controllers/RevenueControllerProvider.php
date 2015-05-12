<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RevenueControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
        
        /*
         * Revenue
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', (int)$game_id);
            $game = $app['getGame']((int)$game_id) ;
            //$game = $this->getGame((int)$game_id) ;
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
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $game
                ));
            }
        })
        ->bind('Revenue');
        
        /*
        * POST target
        * Verb : RevenueDone
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/RevenueDone', function($game_id , Request $request) use ($app)
        {
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
                $this->doRevenue($game , $user_id , $request->request->all()) ;
                $game->getParty($user_id)->setIsDone(TRUE) ;
                if ($game->isEveryoneDone())
                {
                    $app['saveGame']($game) ;
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
        ->bind('verb_RevenueDone');


        return $controllers ;
    }

    private function doRevenue($game , $user_id , $data)
    {
        $base = $game->getParty($user_id)->revenue_base($game->getLegions()) ;
        // Handle normal revenue (senators, leader, knights)
        
        // Handle Concessions, including extra revenue from drought (choice should be in 'data')
        foreach($base['concessions'] as $concession)
        {
            
        }

        // Handle Provincial spoils, choice should be in 'data'
        foreach($base['provinces'] as $province)
        {
            
        }

        // Handle rebel legions maintenance, choice should be in 'data'
        foreach($base['rebels'] as $rebel)
        {
            
        }
        die() ;
    }
    
}