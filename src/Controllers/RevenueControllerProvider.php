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
            $game = $this->getGame((int)$game_id) ;
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

        return $controllers ;
    }

    /**
     * Returns a Game entity corresponding to this $game_id, or FALSE if not found
     * @param int $game_id
     * @return Entity\Game | FALSE
     */
    function getGame($game_id)
    {
        $query = $this->entityManager->createQuery('SELECT g FROM Entities\Game g WHERE g.id = '.(int)$game_id);
        $result = $query->getResult() ;
        return ( (count($result)==1) ? $result[0] : FALSE ) ;
    }

}