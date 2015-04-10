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
                return $app['twig']->render('Setup/Base.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $result[0]
                ));
            }
        })
        ->bind('SetupBase');
        return $controllers ;
    }
}