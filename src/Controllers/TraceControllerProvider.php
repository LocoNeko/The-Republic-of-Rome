<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TraceControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
        
        $controllers->post('/{game_id}/undoTrace', function($game_id) use ($app)
        {
            /** @var \Entities\Game $game */
            $game = $app['getGame']((int)$game_id) ;
            $messages = $game->getMessages() ;
            while ($message = $messages->last())
            {
                if ($message->getTraceDescription()===FALSE)
                {
                    $messages->remove($messages->key());
                }
                else
                {
                    break ;
                }
            }
            $app['session']->getFlashBag()->add('danger', $message->getTraceDescription());
            return $app->redirect('/') ;
        })
        ->bind('trace');

        return $controllers ;
    }
}
