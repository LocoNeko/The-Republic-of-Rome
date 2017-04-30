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

        /*
        * POST target
        * Verb : senateMakeProposal
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateMakeProposal', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                /** @var \Presenters\GamePresenter $gamePresenter */
                $gamePresenter = new \Presenters\GamePresenter($game , $user_id) ;
                /** @var \Entities\Proposal $proposal */
                $proposal = $this->makeProposal($user_id , $game , $json_data);
                $app['session']->getFlashBag()->add('info', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                //$app['session']->getFlashBag()->add('info', $gamePresenter->displayContextualName($proposal->getDescription()));
                $app['session']->getFlashBag()->add('danger', _('TESTING - this doesnt go farther yet !'));
                $game->setNewProposal($proposal) ;
                $app['session']->getFlashBag()->add('danger', $game->getProposals()->last()->getCards()->count());
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                $app['session']->getFlashBag()->add('info', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
        })
        ->bind('verb_senateMakeProposal');

        return $controllers;
    }
    
    /**
     * @param int $user_id
     * @param \Entities\Game $game
     * @param type $json_data
     * @return \Entities\Proposal
     * @throws \Exception
     */
    public function makeProposal($user_id , $game , $json_data)
    {
        // The proposal type is equal to the sub phase, except during otherbusiness in which case it's determined by ???
        // TO DO : Find the json var that holds the type of otherBusiness proposal that is selected
        $subPhase = $game->getSubPhase() ;
        $proposalType = ( ($subPhase=='otherBusiness') ? $json_data['otherBusinessProposal'] : $game->getSubPhase() ) ;
        try 
        {
            $proposal = new \Entities\Proposal($user_id , $proposalType , $game , $json_data) ;
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
        return $proposal ;
    }
}
