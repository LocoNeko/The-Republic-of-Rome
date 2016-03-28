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
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_RollEvent');

        /*
        * POST target
        * Verb : MoveCard
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/MoveCard', function($game_id , Request $request) use ($app)
        {
            /** @var \Entities\Game\ $game */
            $game = $app['getGame']((int)$game_id) ;
            if ($game!==FALSE)
            {
                // TO DO : Move card
                $data = $request->request->all();
                $fromCard = $game->getFilteredCards(array('id' => $data['FromCard'])) ;
                $fromLocation = $fromCard->first()->getDeck();
                $toDeck = $game->getFilteredDecks(array('id' => $data['ToDeck'])) ;
                $fromLocation->getFirstCardByProperty('id' , $fromCard->first()->getId() , $toDeck->first()) ;
                $game->log('DEBUG - Moving card '.$fromCard->first()->getName().' from '.$fromLocation->getName().' to '.$toDeck->first()->getName() , 'alert');
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_MoveCard');

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
                $game->log(_('[['.$user_id.']] {roll,rolls} %1$d and {draw,draws} a card.') , 'log' , array((int)$roll['total']) ) ;
                $card = $game->getDeck('drawDeck')->drawFirstCard() ;
                if ($card !== NULL)
                {
                    /**
                     * Statesman, Faction, Concession
                     */
                    if ($card->getPreciseType()=='Statesman' || $card->getPreciseType()=='Faction card' || $card->getPreciseType()=='Concession')
                    {
                        $game->getParty($user_id)->getHand()->putCardOnTop($card);
                        $game->log(_('[['.$user_id.']] {draw faction card %1$s and put it in your hand.,draws a faction card and puts it in his hand.}') , 'log' , array($card->getName())) ;
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
                        $matchedCards = $game->getFilteredCards( array('matches' => $card->getMatches()) )->toArray() ;
                        // Is there a matched card in the activeWars or inactiveWars decks ?
                        $matchedActive = FALSE ;
                        $matchedInactive = FALSE ;
                        foreach($matchedCards as $key=>$matchedCard)
                        {
                            if($matchedCard->getLocation()['name']=='activeWars')
                            {
                                $matchedActive = $key ;
                            }
                            if($matchedCard->getLocation()['name']=='inactiveWars')
                            {
                                $matchedInactive = $key ;
                            }
                        }
                        // We found a matched card in the activeWars deck - move the drawn card to 'imminent'
                        if ($matchedActive !== FALSE)
                        {
                            $game->getDeck('imminentWars')->putCardOnTop($card) ;
                            $game->log(_('[['.$user_id.']] {draw,draws} %1$s, there is a matched conflict, the card goes to the imminent deck.') , 'log' , array($card->getName())) ; 
                        }
                        // We found a matched war in the inactiveWars deck
                        // The card goes to the imminent deck and the inactive card is now active
                        elseif ($matchedInactive !== FALSE)
                        {
                            $game->getDeck('imminentWars')->putCardOnTop($card) ;
                            $game->getDeck('inactiveWars')->getFirstCardByProperty('id', $matchedCards[$matchedInactive]->getId() , $game->getDeck('activeWars'));
                                $game->log(
                                    _('[['.$user_id.']] {draw,draws} %1$s, the card goes to the imminent deck and the inactive card %2$s is now active.') ,
                                    'log' ,
                                    array(
                                        $card->getName() ,
                                        $matchedCards[$matchedInactive]->getName()
                                    )
                                ) ; 
                        }
                        // The armed icon sends the card to the activeWars deck
                        elseif($card->getActive())
                        {
                            $game->getDeck('activeWars')->putCardOnTop($card) ;
                            $game->log(_('[['.$user_id.']] {draw,draws} %s, there are no matched conflicts, so based on the card\'s icon, the war is now active.') , 'log' , array($card->getName())) ; 
                        }
                        // The armed icon sends the card to the inactiveWars deck
                        else
                        {
                            $game->getDeck('inactiveWars')->putCardOnTop($card) ;
                            $game->log(_('[['.$user_id.']] {draw,draws} %s, there are no matched conflicts, so based on the card\'s icon, the war is now inactive.') , 'log' , array($card->getName())) ; 
                        }
                        // TO DO : Handle activating leaders in the Curia - most of the code below can be copied
                        
                        /*
                        else
                        {
                            // Move any matched leaders from the curia to the Conflict card
                            foreach ($game->getDeck('curia')->getCards() as $curiaCard) {
                                if ($curiaCard->getPreciseType()=='Leader' && $curiaCard->getMatches()==$card->getMatches())
                                {
                                    $pickedLeader = $game->getDeck('curia')->getFirstCardByProperty('id' , $curiaCard->getId()) ;
                                    $card->getCardsControlled()->putCardOnTop($pickedLeader) ;
                                    // A leader activates an inactive conflict
                                    $activationMessage='' ;
                                    if ($activateInCaseOfLeader)
                                    {
                                        $game->getDeck('inactiveWars')->getFirstCardByProperty('id', $card->getId()) ;
                                        $activationMessage = _(' This activates the conflict.'); 
                                    }
                                    $game->log(
                                        _('The leader %1$s is matched with %2$s, so moves from the Curia to the card.%3$s') ,
                                        'log' ,
                                        array($pickedLeader->getName() , $card->getName() , $activationMessage)
                                    ) ; 
                                }
                            }
                        }
                        */
                    }
                    /**
                     * Leader
                     */
                    elseif ($card->getPreciseType()=='Leader')
                    {
                        $matchedWar = $game->getFilteredCards( array ('preciseType' => 'Conflict' , 'matches' => $card->getMatches() ) )->toArray() ;
                        // Only keep active, inactive wars
                        foreach($matchedWar as $key=>$war)
                        {
                            if($war->getLocation()['name']!='activeWars' && $war->getLocation()['name']!='inactiveWars' && $war->getLocation()['name']!='unprosecutedWars')
                            {
                                unset($matchedWar[$key]);
                            }
                        }
                        // Order cards based on their decks : active first, then inactive, so the leader goes on an active war first
                        usort($matchedWar , function ($a , $b)
                        {
                            return ( ($a->getLocation()['name']=='activeWars') ? 1 : -1 ) ;
                        }) ;
                        // There is no matching conflict, the leader goes to the Curia
                        if (count($matchedWar)==0)
                        {
                            $game->getDeck('curia')->putCardOnTop($card) ;
                            $game->log(_('[['.$user_id.']] {draw,draws} %s, without a matched conflict, the card goes to the Curia.') , 'log' , array($card->getName())) ; 
                        }
                        // There is a matching conflict.
                        else
                        {
                            reset($matchedWar)->getCardsControlled()->putCardOnTop($card) ;
                            // Activate the war if it was not
                            $activationMessage='' ;
                            if (reset($matchedWar)->getLocation()['name']=='inactiveWars')
                            {
                                $game->getDeck('inactiveWars')->getFirstCardByProperty('id', reset($matchedWar)->getId() , $game->getDeck('activeWars')) ;
                                $activationMessage = _(' This activates the conflict.'); 
                            }
                            $game->log(
                                _('[['.$user_id.']] {draw,draws} %1$s, which is placed on the matched conflict %2$s.%3$s') ,
                                'log' ,
                                array(
                                    $card->getName() ,
                                    reset($matchedWar)->getName() ,
                                    $activationMessage
                                )
                            ) ; 
                        }
                    }
                    /**
                     * Other
                     */
                    else
                    {
                        $game->getDeck('forum')->putCardOnTop($card) ;
                        $game->log(_('[['.$user_id.']] {draw,draws} %s that goes to the forum.') , 'log' , array($card->getName())) ; 
                    }
                }
                else
                {
                    $game->log(_('There is no more cards in the deck.') , 'alert') ;
                }
            }
        }
        else
        {
            return FALSE ;
        }
    }

}
