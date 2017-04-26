<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SenateControllerProvider implements ControllerProviderInterface
{
    private $entityManager;

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'];

        /*
         * Senate
         */
        $controllers->get('/{game_id}', function ($game_id) use ($app) {
            $app['session']->set('game_id', (int)$game_id);
            try {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id);
                $user_id = (int)$app['user']->getId();

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                $game->getParty($user_id)->setLastUpdateToNow();
		
		try
                {
                    $view = new \Presenters\SenatePhasePresenter($game, $user_id);
                }
                catch (\Exception $exception) 
                {
                    $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                    return $app->redirect('/');
                }
                
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'InGameLayout.twig',
                    'view' => $view
                ));
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->redirect('/');
            }
        })
        ->bind('Senate');

        return $controllers;
    }
}
