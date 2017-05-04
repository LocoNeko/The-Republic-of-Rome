<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PopulationControllerProvider implements ControllerProviderInterface
{
    private $entityManager;

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'];

        /*
         * Population
         */
        $controllers->get('/{game_id}', function ($game_id) use ($app) {
            $app['session']->set('game_id', (int)$game_id);
            try {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id);
                $user_id = (int)$app['user']->getId();

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                $game->getParty($user_id)->setLastUpdateToNow();

                $view = new \Presenters\PopulationPhasePresenter($game, $user_id);

                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'InGameLayout.twig',
                    'view' => $view
                ));
            } catch (\Exception $exception) {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->redirect('/');
            }
        })
        ->bind('Population');

        /*
        * POST target
        * Verb : populationSpeech
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/populationSpeech', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $roll = $game->rollDice(3 , -1) ;
                $unrest = $game->getUnrest() ;
                $HRAO_POP = $game->getHRAO()->getPOP() ;
                $total = max (-1 , min (18 , $roll['total'] - $unrest + $HRAO_POP)) ;
                $game->log(_('%1$s rolls %2$d%3$s - current unrest %4$d + his POP %5$d for a modified total of %6$d.') , 'log' , array (
                    $game->getHRAO()->getName() ,
                    $roll['total'] ,
                    $game->getEvilOmensMessage(-1) ,
                    $unrest ,
                    $HRAO_POP ,
                    $total
                )) ;
                if ($total==-1)
                {
                    $game->log(_('GAME OVER')) ;
                }
                else
                {
                    $effects = $game->getPopulationEffects($total) ;
                    foreach (explode(',' , $effects) as $effect)
                    {
                        if ($effect == 'NR')
                        {
                            $game->putEventInPlay('name' , 'No Recruitment') ;
                        }
                        elseif ($effect == 'MS')
                        {
                            $game->putEventInPlay('name' , 'Manpower Shortage') ;
                        }
                        elseif ($effect == 'Mob')
                        {
                            $game->putEventInPlay('name' , 'Mob') ;
                        }
                        elseif ($effect==0)
                        {
                            $game->log(_('Unrest is unchanged')) ;
                        }
                        else
                        {
                            $game->changeUnrest($effect) ;
                            $game->log( _('Unrest is %1$s by %2$d') , 'log' , array( ( $effect>0 ? _('increased') : _('decreased') ) , $effect) ) ;
                        }
                    }
                }
                $game->setPhase('Senate') ;
                $game->setSubPhase('Consuls') ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
        })
        ->bind('verb_populationSpeech');

        return $controllers ;
    }
}
