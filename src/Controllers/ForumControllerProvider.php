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
                $this->doRollEvent($user_id , $game) ;
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
     * @param int $user_id
     * @param \Entities\Game $game
     * @return boolean
     */
    public function doRollEvent($user_id , $game)
    {
        if ($game->getPhase()=='Forum' && $game->getSubPhase()=='RollEvent')
        {
            $roll = $game->rollDice(2, 0) ;
            /*
             * A 7 was rolled - an event is played
             */
            $roll['total']=7;
            if ($roll['total']==7)
            {
                $eventRoll = $game->rollDice(3, 0) ;
                $eventNumber = $game->getEventTable()[(int)$eventRoll['total']][$game->getScenario()];
                $game->log(_('[['.$user_id.']] {roll,rolls} a 7, then a %1$d on the event table.') , 'log' , array((int)$eventRoll['total']) ) ;

                $game->putEventInPlay('number', $eventNumber);
            }
            /*
             * A 7 was not rolled - The player draws a card
             */
            else
            {
                $game->log(_('[['.$user_id.']] {roll,rolls} %1$d and draws a card.') , 'log' , array((int)$eventRoll['total']) ) ;
                $card = $game->getDeck('drawDeck')->drawFirstCard() ;
                if ($card !== NULL)
                {
                    /**
                     * Statesman, Faction, Concession
                     */
                    if ($card->getPreciseType()=='Statesman' || $card->getPreciseType()=='Faction' || $card->getPreciseType()=='Concession')
                    {
                        $game->getParty($user_id)->getHand()->putCardOnTop($card);
                        $game->log(_('[['.$user_id.']] {draw a faction card and put it in your hand.,draws a faction card and puts it in his hand.}')) ;
                    }
                    /**
                     * Family - Check if a corresponding Statesman is in play
                     */
                    elseif ($card->getPreciseType()=='Family')
                    {
                        // Make a list of possible Statesmen
                        $possibleStatemen = array() ;
                        foreach ($game->getParties() as $party)
                        {
                            foreach ($party->getSenators()->getCards() as $senator)
                            {
                                if ($senator->getPreciseType()=='Statesman' && $senator->statesmanFamily() == $card->getSenatorID())
                                {
                                    array_push($possibleStatemen , array('senator' => $senator , 'party' => $party)) ;
                                }
                            }
                        }
                        $game->log(_('[['.$user_id.']] {draw,draws} %1$s') , 'log' , array($card->getName()) ) ;
                        // No corresponding statesman : Family goes to the Forum
                        if (count($possibleStatemen)==0)
                        {
                            $game->getDeck('forum')->putCardOnTop($card) ;
                            $game->log(_('he goes to the forum.'));
                        }
                        // Found one or more (in case of brothers) corresponding Statesmen : put the Family under them
                        // Case 1 : only one Statesman
                        elseif (count($possibleStatemen)==1)
                        {
                            $possibleStatemen[0]['senator']->getControlled_by()->putCardOnTop($card) ;
                            $game->log(_('[['.$possibleStatemen[0]['party']->getUser_id().']] {have,has} %1$s so the family joins him') , 'log' , array($possibleStatemen[0]['senator']->getName()) ) ;
                        // Case 2 : brothers are in play
                        }
                        else
                        {
                            // Sorts the possibleStatemen in SenatorID order, so 'xxA' is before 'xxB'
                            // This is only relevant to brothers
                            usort ($possibleStatemen, function($a, $b) {
                                return strcmp($a['senator']->getSenatorID() , $b['senator']->getSenatorID());
                            });
                            $possibleStatemen[0]['senator']->getControlled_by()->putCardOnTop($card) ;
                            $game->log(_('[['.$possibleStatemen[0]['party']->getUser_id().']] {have,has} %1$s (who has the letter "A" and takes precedence over his brother) so the family joins him') , 'log' , array($possibleStatemen[0]['senator']->getName()) ) ;
                        }

                    }
                    /**
                     * Conflict
                     */
                    elseif ($card->getPreciseType()=='Conflict')
                    {
                        
                    }
                    /**
                     * Leader
                     */
                    elseif ($card->getPreciseType()=='Leader')
                    {
                        
                    }
                    /**
                     * Other
                     */
                    else
                    {
                        
                    }
                }
                else
                {
                    $game->log(_('There is no more cards in the deck.') , 'alert') ;
                }
            }
            $this->entityManager->persist($game);
            $this->entityManager->flush();
        }
        else
        {
            return FALSE ;
        }
    }

}
