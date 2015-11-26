<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ForumControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
    
        /*
         * Forum
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', (int)$game_id);
            $game = $app['getGame']((int)$game_id) ;
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
        ->bind('Forum');
        
        /*
        * POST target
        * Verb : RollEvent
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/RollEvent', function($game_id , Request $request) use ($app)
        {
            /** @var \Entities\Game\ $game */
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
                return $app->json( 'SUCCESS' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_RollEvent');

        return $controllers ;
    }
    
    // TO DO : Check if this should be here
    /**
     * A message saying who is currently the highest bidder<br>
     * The message can also indicate that the HRAO currently would have the initiative if nobody is betting
     * @param Game $game
     * @return array 'bid','message','user_id'
     */
    public function forum_highestBidder ($game)
    {
        $result['bid']=0 ;
        $result['message']='' ;
        foreach ($game->getParties() as $party)
        {
            if ($party->getBid() > $result['bid'])
            {
                $result['bid']=$party->getBid() ;
                $result['user_id'] = $party->getUser_id();
                $result['message'] = sprintf(_(' %1$s with a bid of %2$dT.') , $party->getFullName() , $result['bid']) ;
            }
        }
        if ($result['bid']==0)
        {
            $HRAO = $game->getHRAO();
            $result['message'] = sprintf(_('The HRAO (%s) as all bets are 0.') , $game->getParty($HRAO['user_id'])->getFullName());
            $result['user_id'] = $HRAO['user_id'];
        }
        return $result ;
    }
    
    /**
     * 
     * @param \Entities\Game $game
     * @return boolean
     */
    public function doRollEvent($game)
    {
        if ($game->getPhase()=='Forum' && $game->getSubPhase()=='RollEvent')
        {
            $roll = $game->rollDice(2, 0) ;
            /*
             * A 7 was rolled - an event is played
             */
            if ($roll['total']==7)
            {
                $eventRoll = $game->rollDice(3, 0) ;
                $eventNumber = $game->getEventTable()[(int)$eventRoll['total']][$game->getScenario()];

            /*
                $eventMessage = $this->forum_putEventInPlay('number' , $eventNumber) ;
                foreach ($eventMessage as $message) {
                    $messages[] = $message;
                }
            */
            }
            else
            {
            }
        }
        else
        {
            return FALSE ;
        }
    }

}
