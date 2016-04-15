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
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->redirect('/') ;
            }
            
            /*
            $gameView = new \Presenters\GamePresenter($game) ;
            $forumView = new \Presenters\ForumPhasePresenter($game , (int)$app['user']->getId()) ;
            
            return $app['twig']->render('BoardElements/Main.twig', array(
                'layout_template' => 'layout.twig' ,
                'game' => $game ,
                'gameView' => $gameView ,
                'header' => $forumView->getHeader() ,
                'content' => $forumView->getContent()
            ));
            */
            
            $user_id = (int)$app['user']->getId() ;

            //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
            // TO DO : See how to handle this update better (service ?)
            $game->getParty($user_id)->setLastUpdateToNow() ;
            
            $view = new \Presenters\ForumPhasePresenterNew($game , $user_id) ;

            return $app['twig']->render('BoardElements/Main_new.twig', array(
                    'layout_template' => 'InGameLayout.twig' ,
                    'view' => $view
            ));
        })
        ->bind('Forum');
        
        /*
        * POST target
        * Verb : RollEvent
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/RollEvent', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            // TO DO : user id should be passed in JSON
            $user_id = (int)$app['user']->getId() ;
            $this->doRollEvent($user_id , $game) ;
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_RollEvent');

        /*
        * POST target
        * Verb : noPersuasion
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/noPersuasion', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            // TO DO : user id should be passed in JSON
            $user_id = (int)$app['user']->getId() ;
            $game->log(_('[['.$user_id.']] {don\'t,doesn\'t} make a persuasion attempt.') , 'log' ) ;
            $game->setSubPhase('knights') ;
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_noPersuasion');

        /*
        * POST target
        * Verb : persuasionPickTarget
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/persuasionPickTarget', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            // TO DO : user id should be passed in JSON
            $user_id = (int)$app['user']->getId() ;
            $outcome = $this->persuasionPickTarget($game , $user_id , $request->request->all()) ;
            /**
             * SUCCESS - No error while picking the target
             */
            if ($outcome===TRUE)
            {
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            /*
             * ERROR - Error while picking the target
             */
            else
            {
                $app['session']->getFlashBag()->add('danger' , $outcome) ;
                return $app->json( $outcome , 201);
            }
        })
        ->bind('verb_persuasionPickTarget');

        /*
        * POST target
        * Verb : persuasionCounterBribe
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/persuasionCounterBribe', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            $json_data = $request->request->all() ;
            $user_id = (int)$json_data['user_id'] ;
            $game->getParty($user_id)->setIsDone(TRUE) ;
            $game->getParty($user_id)->changeBid($json_data['persuasionCounterBribeAmount']);
            $game->log(_('[['.$user_id.']] {increase,increases} bribes by %d') , 'log' , array($json_data['persuasionCounterBribeAmount'])) ;
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_persuasionCounterBribe');

        /*
        }
        * POST target
        * Verb : persuasionNoCounterBribe
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/persuasionNoCounterBribe', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            $json_data = $request->request->all() ;
            $user_id = (int)$json_data['user_id'] ;
            // User_id mismatch
            if ( $user_id!=(int)$app['user']->getId() )
            {
                $app['session']->getFlashBag()->add('danger', _('Error - User ID mismatch.'));
                return $app->json( _('Error - User ID mismatch.') , 201);
            }
            else
            {
                $game->getParty($user_id)->setIsDone(TRUE) ;
                $game->log(_('[['.$user_id.']] {don\'t,doesn\'t} counter-bribe') , 'log');
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
        })
        ->bind('verb_persuasionNoCounterBribe');

        /**
        * POST target
        * Verb : bribeMore
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/persuasionBribeMore', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            $json_data = $request->request->all() ;
            $user_id = (int)$json_data['user_id'] ;
            // User_id mismatch
            if ( $user_id!=(int)$app['user']->getId() )
            {
                $app['session']->getFlashBag()->add('danger', _('Error - User ID mismatch.'));
                return $app->json( _('Error - User ID mismatch.') , 201);
            }
            else
            {
                // Increase the bribe
                $game->getParty($user_id)->changeBid($json_data['persuasionAddedBribe']);
                // Set all parties to isDone = FALSE
                foreach ($game->getParties() as $party)
                {
                    $party->setIsDone(FALSE) ;
                }
                $game->getParty($user_id)->setIsDone(TRUE) ;
                $game->log(_('[['.$user_id.']] {increase,increases} bribes by %d') , 'log' , array($json_data['persuasionAddedBribe'])) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
        })
        ->bind('verb_persuasionBribeMore');
        
        /**
        * POST target
        * Verb : persuasionRoll
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/persuasionRoll', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            $json_data = $request->request->all() ;
            $user_id = (int)$json_data['user_id'] ;
            // User_id mismatch
            if ( $user_id!=(int)$app['user']->getId() )
            {
                $app['session']->getFlashBag()->add('danger', _('Error - User ID mismatch.'));
                return $app->json( _('Error - User ID mismatch.') , 201);
            }
            else
            {
                $this->persuasionRoll($game, $user_id, $json_data) ;
                $game->setSubPhase('knights') ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
        })
        ->bind('verb_persuasionRoll');
        
        /*
         * 
         * =============== DEBUG ===============
         * 
        */
        
        /*
        * POST target
        * Verb : MoveCard (debug function)
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/MoveCard', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (Exception $exception)
            {
                $app['session']->getFlashBag()->add('alert', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
            $data = $request->request->all();
            $fromCard = $game->getFilteredCards(array('id' => $data['FromCard'])) ;
            $fromLocation = $fromCard->first()->getDeck();
            $toDeck = $game->getFilteredDecks(array('id' => $data['ToDeck'])) ;
            $fromLocation->getFirstCardByProperty('id' , $fromCard->first()->getId() , $toDeck->first()) ;
            $game->log('DEBUG - Moving card '.$fromCard->first()->getName().' from '.$fromLocation->getName().' to '.$toDeck->first()->getName() , 'alert');
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_MoveCard');

        return $controllers ;
    }
    
    /**
     * 
     * CONTROLLER'S FUNCTIONS
     * 
     */

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
                        // Activate matched leaders in the Curia
                        foreach ($game->getDeck('curia')->getCards() as $curiaCard)
                        {
                            if ($curiaCard->getPreciseType()=='Leader' && $curiaCard->getMatches()==$card->getMatches())
                            {
                                $pickedLeader = $game->getDeck('curia')->getFirstCardByProperty('id' , $curiaCard->getId() , $card->getCardsControlled()) ;
                                // A leader activates an inactive conflict
                                $activationMessage='' ;
                                if ( ($card->getLocation()['type'] == 'game') && ($card->getLocation()['value'] == 'inactiveWars') )
                                {
                                    $game->getDeck('inactiveWars')->getFirstCardByProperty('id', $card->getId() , $game->getDeck('activeWars')) ;
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
                        $game->log(_('[['.$user_id.']] {draw,draws} %s. The card goes to the forum.') , 'log' , array($card->getName())) ; 
                    }
                }
                else
                {
                    $game->log(_('There is no more cards in the deck.') , 'alert') ;
                }
            }
            $game->setSubPhase('Persuasion');
            $this->resetPersuasion($game);
            $game->setCurrentBidder($game->getParty($user_id)) ;
        }
        else
        {
            return FALSE ;
        }
    }
    
    /**
     * Resets persuasion vars
     * @param \Entities\Game $game
     */
    public function resetPersuasion($game)
    {
        foreach($game->getParties() as $party)
        {
            /* @var \Entities\Party $party */
            $party->setBid(FALSE) ;
            $party->setIsDone(FALSE) ;
        }
        $game->setCurrentBidder(NULL) ;
        $game->setPersuasionTarget(NULL) ;
    }

    /**
     *
     * @param \Entities\Game $game
     * @param int $user_id
     * @param array $data
     * @return string | boolean TRUE if successful, an erro message otherwise
     */
    public function persuasionPickTarget($game , $user_id , $data)
    {
        $target = $game->getFilteredCards(array('senatorID'=>$data['persuasionTargetList'])) ;
        $persuader= $game->getFilteredCards(array('senatorID'=>$data['persuasionPersuaderList'])) ;
        $bribe = (int)$data['persuasionBribe'] ;
        $persuasionCard = $game->getFilteredCards(array('id'=>$data['persuasionCard'])) ;
        // validation
        if (count($target)!=1)
        {
            return _('Wrong target') ;
        }
        if ($target->first()->getLocation()['type']!='party' && $target->first()->getLocation()['name']!='forum')
        {
            return _('Target not in a party or the forum') ;
        }
        if (count($persuader)!=1)
        {
            return _('Wrong persuader') ;
        }
        if ($persuader->first()->getLocation()['type']!='party' || $persuader->first()->getLocation()['value']->getUser_id()!=$user_id)
        {
            return _('Persuader in wrong party') ;
        }
        if ($bribe<0 || $bribe>$persuader->first()->getTreasury())
        {
            return _('Invalid bribe value') ;
        }
        if (count($persuasionCard)>0 && ( count($persuasionCard->first())!=1 || $persuasionCard->first()->getLocation()['type']!='hand' || $persuasionCard->first()->getLocation()['value']->getUser_id()!=$user_id))
        {
            return _('Persuasion card comes from the wrong place') ;
        }
        /*
         * Validation done. Set persuader as $party->bidWith , bribe as $party->bid and target as $game->persuasiontarget
         */
        $game->getParty($user_id)->setBidWith($persuader->first()) ;
        $game->setPersuasionTarget($target->first()) ;
        $game->getParty($user_id)->setBid($bribe) ;
        $game->getParty($user_id)->setIsDone(TRUE);
        // A persuasion card was played
        if (count($persuasionCard->first())==1)
        {
            // TO DO : Validate card
            // TO DO : If the card bypasses the proces entirely, we can jump to persuasionRoll :
            //$this->persuasionRoll($game, $user_id, $data) ;
        }
        return TRUE ;
    }
    
    /**
     * 
     * @param \Entities\Game $game
     * @param int $user_id
     * @param array $data
     */
    public function persuasionRoll($game , $user_id , $data)
    {
        // TO DO : Persuasion Card
        /*
         * Validation
         */
        $forumView = new \Presenters\ForumPhasePresenter($game , $user_id) ;
        $error = FALSE ;
        if ($forumView->getPartyWithInitiative()===FALSE || $forumView->getPartyWithInitiative()->getUser_id()!=$user_id )
        {
            $error = _('ERROR - Wrong party') ;
        }
        elseif ($forumView->getPersuasionTarget()===NULL)
        {
            $error = _('ERROR - No target') ;
        }
        elseif ($forumView->getPartyWithInitiative()->getBid() > $forumView->getPartyWithInitiative()->getBidWith()->getTreasury())
        {
            $error = _('ERROR - Wrong bid amount') ;
        }
        /*
         * Validation complete - proceed
         */
        if ($error===FALSE)
        {
            $persuasionDescription = $forumView->getPersuasionDescription() ;
            $roll = $game->rollDice(2, 1) ;
            $for = $persuasionDescription['for'] ;
            $against = $persuasionDescription['against'] ;
            $targetValue = $for-$against ;
            $message = _('ERROR - impossible value. ');
            $success = FALSE ;
            /*
             * CHeck outcome : failure on 10+ . failure if roll greater than target , success otherwise
             */
            if ($roll['total']>=10)
            {
                $message = sprintf(_('FAILURE - %1$s He rolls an unmodified %2$d%3$s, which is greater than 9 and an automatic failure. ') , $persuasionDescription['text'] , $roll['total'] , $game->getEvilOmensMessage(1)) ;
            }
            elseif ($roll['total']>$targetValue)
            {
                $message = sprintf(_('FAILURE - %1$s He rolls %2$d%3$s, which is greater than the target number of %4$d. ') , $persuasionDescription['text'] , $roll['total'] , $game->getEvilOmensMessage(1) , $targetValue) ;
            }
            else
            {
                $success = TRUE ;
                $message = sprintf(_('SUCCESS - %1$s He rolls %2$d%3$s, which is less than the target number of %4$d. ') , $persuasionDescription['text'] , $roll['total'] , $game->getEvilOmensMessage(1) , $targetValue) ;
                $forumView->getPersuasionTarget()->getLocation()['deck']->getFirstCardByProperty('senatorID' , $forumView->getPersuasionTarget()->getSenatorID() , $game->getParty($user_id)->getSenators());
            }
            
            /**
             * Describe what the target does (stay or go)
             */
            $message2 = $forumView->getPersuasionTarget()->getName().
            (   $success ?
                _(' joins ').$forumView->getPartyWithInitiative()->getName().'.' : 
                _(' stays in ').$forumView->getPersuasionTarget()->getLocation()['name'].'.'
            );
            
            /**
             * Bribes received
             */
            $totalBribesReceived = 0 ;
            foreach ($game->getParties() as $party)
            {
                $totalBribesReceived+=$party->getBid() ;
                $forumView->getPersuasionTarget()->changeTreasury($party->getBid()) ;
                // Take bribes from party treasury for every party but the party with the initiative, for which bribes come from the persuader's treasury
                if ($forumView->getPartyWithInitiative()->getUser_id()==$party->getUser_id())
                {
                    $party->getBidWith()->changeTreasury(-$party->getBid()) ;
                }
                else
                {
                    $party->changeTreasury(-$party->getBid()) ;
                }
            }
            if ($totalBribesReceived>0)
            {
                $message2.=sprintf(_(' He takes a total of %1$dT in bribes.') , $totalBribesReceived);
            }
            
            $game->log($message, 'log');
            $game->log($message2, 'log');
        }
        /*
         * There was an error
         */
        else
        {
            $game->log($error , 'error');
        }
        /*
         * Reset persuasion
         */
        $this->resetPersuasion($game) ;
    }
}
