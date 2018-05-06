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
    /**
     * Events relevant to combats
     * 161;
     * Ally Deserts : Roman allies are wavering. All battles fought this turn with an even result on 3d6 will result in a temporary increase to the War cards's strength for this turn equal to the result of the black die. This increase is applied after any multipliers for Matching Wars;
     * level 2 : Roman Auxiliary Deserts : Roman allies are shaken. All battles fought this turn with an even result on 3d6 will result in a temporary increase to the War cards's strength for this turn equal to the result of the white dice. This increase is applied after any multipliers for Matching Wars ;
     * 
     * 166;
     * Enemy's Ally Deserts : All battles fought this turn with an odd result on 3d6 will result in a temporary decrease to the War's Strength for this turn equal to the result on the black die. THis decrease is applied after any multipliers for Matching Wars. The minimum Strength it can be lowered to is 0.;
     * level 2 : Enemy Mercenaries Desert : All battles fought this turn with an odd result on 3d6 will result in a temporary decrease to the War's Strength for this turn equal to the result on the white dice. This decrease is applied after any multipliers for Matching Wars. The minimum Strength it can be lowered to is 0.;
     * 
     * 168;
     * Evil Omens :The State Treasury must immediately pay 20T for sacrifices and Temple repair and a -1 penalty is applied to EVERY die or dice roll except for Initiative and further rolls on the Events Table (Exception: +1 to all Persuasion Attempts.) In the case of Provincial Spoils and State Income subtract one from the total income - not from the die roll.;
     */
}
