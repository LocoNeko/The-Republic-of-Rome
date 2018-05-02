<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class CombatControllerProvider implements ControllerProviderInterface
{
    private $entityManager;

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'];

        /*
         * Combat
         */
        $controllers->get('/{game_id}', function ($game_id) use ($app) {
            $app['session']->set('game_id', (int)$game_id);
            try {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id);
                $user_id = (int)$app['user']->getId();
                $presenter = new \Presenters\CombatPhasePresenter($game, $user_id);
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'InGameLayout.twig',
                    /** @todo This is ugly as shit. replace view by presenter everywhere (in all Presenters !*/
                    'view' => $presenter
                ));

            }
            catch (\Exception $ex) 
            {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $ex->getFile(), $ex->getLine(), $ex->getMessage(), get_class($ex))); } while($ex = $ex->getPrevious());
                return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
            }
        })
        ->bind('Combat');

        return $controllers;
    }
}
