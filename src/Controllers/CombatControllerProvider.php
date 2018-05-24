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

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                $game->getParty($user_id)->setLastUpdateToNow();

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

        /*
        * POST target
        * Verb : combatRoll
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/combatRoll', function($game_id , Request $request) use ($app)
        {
            try
            {
                /* @var $game \Entities\Game  */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $game->doBattle($user_id) ;
                /**
                 * Check : 
                 * - Move wars to unprosecuted if applicable
                 */
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $ex) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $ex->getFile(), $ex->getLine(), $ex->getMessage(), get_class($ex))); } while($ex = $ex->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_combatRoll');

        /*
        * POST target
        * Verb : combatBattleOrder
        * > The choices must be saved in the Proposal 'battleOrder' as a number (1 = fight first , 2 = fight second, etc)
        * > When a commander chooses the order, see if there are no more 'pending' battleResult, in which case :
        * > Check if the commanders agree on the order, assign randomly if they don't
        * > Once there are no more battleOrder action button to show, we can show only combatRoll
        * JSON data : user_id
        */
        
        /*
        * POST target
        * Verb : combatLandBattle
        * JSON data : user_id
        */
     
        return $controllers;
    }
}
