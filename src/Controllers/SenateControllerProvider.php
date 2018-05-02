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
                    $app['session']->getFlashBag()->add('danger', $exception->getTraceAsString());
                    return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
                }
                
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'InGameLayout.twig',
                    'view' => $view
                ));
            }
            catch (\Exception $exception)
            {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
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
                /* @var $proposal \Entities\Proposal */
                /** @todo remove when happy (DEBUG) : $app['session']->getFlashBag()->add('danger', ' SenateMakeProposal json : '.json_encode($json_data, JSON_PRETTY_PRINT)); */
                try {
                    $proposal = $this->makeProposal($user_id , $game , $json_data);
                } catch (Exception $exception) {
                    $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                    return $app->json( $exception->getMessage() , 201 );
                }
                $game->setNewProposal($proposal) ;
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_senateMakeProposal');

        /*
        * POST target
        * Verb : senateVote
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateVote', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $this->vote($user_id , $game , $json_data) ;
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_senateVote');

        /*
        * POST target
        * Verb : senateVeto
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateVeto', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $this->veto($user_id , $game , $json_data) ;
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_senateVeto');

        /*
        * POST target
        * Verb : senateDecide
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateDecide', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $this->decide($user_id , $game , $json_data , $this->entityManager) ;
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_senateDecide');
        
        /*
        * POST target
        * Verb : endProsecutions
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/endProsecutions', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $game->log(_('The Censor ends prosecutions and returns the floor to the HRAO.'));
                $this->setNextSubPhase($game) ;
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_endProsecutions');
        
        /*
        * The President adjourns the Senate
        * Give all players who are not the President a chance to keep the Senate open by making a proposal (if they can)
        * POST target
        * Verb : senateAdjourn
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateAdjourn', function($game_id , Request $request) use ($app)
        {
            try
            {
                /* @var $game \Entities\Game  */
                $game = $app['getGame']((int)$game_id) ;
                $game->log(_('The President adjourns the Senate.'));
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                /*
                 * We keep track of who wants to adjourn the Senate by checking party->isDone
                 * So we set it to TRUE for the President
                 */
                $game->getParty($user_id)->setIsDone(TRUE) ;
                 /**
                 * - If nobody can keep the Senate open, adjourn the Senate (move to Combat phase) 
                 * - If someone can, bring up the otherbusiness interface
                 * - Upon completing the proposal, the Senate must be adjourned, so when implementing a proposal, always check if subPhase = otherbusiness and the President is done
                 */
                $this->persistFlush($game);
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                do { $app['session']->getFlashBag()->add('danger', sprintf("%s:%d %s [%s]", $exception->getFile(), $exception->getLine(), $exception->getMessage(), get_class($exception))); } while($exception = $exception->getPrevious());
                return $app->json( '' , 201 );
            }
        })
        ->bind('verb_endProsecutions');
        
        return $controllers;
    }
    
    /**
     * Persists $entity and calls flush() on the entity manager. This makes the entity's id available, which is useful for proposals
     * @param type $entity
     */
    private function persistFlush($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
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
        // The proposal type is equal to the sub phase, except during otherbusiness in which case it's determined by $json_data['otherBusinessList']
        $subPhase = $game->getSubPhase() ;
        $proposalType = ( ($subPhase=='OtherBusiness') ? $json_data['otherBusinessList'] : $game->getSubPhase() ) ;
        try 
        {
            $proposal = new \Entities\Proposal($user_id , $proposalType , $game , $json_data) ;
            // I need to persist the proposal before calling recordTrace, or I wouldn't have its id
            $this->persistFlush($proposal);
            $game   ->log($proposal->getDescription(), 'log')
                    ->recordTrace('Proposal', array('action' => 'makeProposal') , array('proposal' => $proposal)) ;
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
        return $proposal ;
    }
    
    /**
     * @param int $user_id
     * @param \Entities\Game $game
     * @param type $json_data
     * @throws \Exception
     */
    public function vote($user_id , $game , $json_data)
    {
        /* @var $currentProposal \Entities\Proposal  */
        $currentProposal = $game->getProposals()->last() ;
        $partyName = $game->getParty($user_id)->getName() ;
        // Check if the user_id is equal to the current voter for this proposal (proposal is underway and current proposal->vote user_id = user_id)
        try {
            if ($currentProposal->getCurrentVoter()!=(int)$user_id)
            {
                throw new \Exception(sprintf(_('Current voter and user mismatch. Current voter ID : %1$s, user_id : %2$s') , $currentProposal->getCurrentVoter() , $user_id)) ;
            }
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
        $jsonVotes = $json_data['toggles'] ;
        $voteTally = $currentProposal->getVoteTally($user_id) ;
        $totalVotes = 0 ;
        $splitVoteDetail=['FOR' => '' , 'AGAINST' => '' , 'ABSTAIN' => ''];
        $isPartyVote = array_key_exists('partyVote', $jsonVotes) ;
        $signDescription='';
        $talentsSpent = [] ;
        $totalTalentsAdded = 0 ;
        $unanimous = NULL ;

        /*
         * Talents : spend them if required. If impossible, throw exception.
         */
        foreach($json_data as $key=>$value)
        {
            $senatorID = str_replace("senatorVoteTalents_" , "" , $key) ;
            // We have found a senator who spent money
            if ($senatorID!=$key && (int)$value>0)
            {
                /* @var $senator \Entities\Senator */
                $senator = $game->getParty($user_id)->getSenators()->getFirstCardByProperty('senatorID', $senatorID) ;
                if ($senator->getTreasury()<$value)
                {
                    throw new \Exception(sprintf(_('%1$s doesn\'t have enough money') , $senator->getName())) ;
                }
                else
                {
                    // Spend talents & add to votes total
                    $senator->changeTreasury(-$value) ;
                    $talentsSpent[$senatorID] = (int)$value ;
                    $totalTalentsAdded+=$value;
                }
            }
        }
        /*
         * Was it a whole party vote or split by Senators ?
         */
        $unanimousSign = NULL ;
        foreach ($voteTally as $voteOfSenator)
        {
            // addedtalents
            $addedTalents = (array_key_exists($voteOfSenator['senatorID'] , $talentsSpent) ? $talentsSpent[$voteOfSenator['senatorID']] : 0 );
            $addedTalentsMessage = ($addedTalents>0 ? '+'.$addedTalents.'T':'');
            if ( $isPartyVote && $jsonVotes['partyVote']=='FOR')         { $sign =  1 ; $signDescription = _('FOR') ; }
            elseif ( $isPartyVote && $jsonVotes['partyVote']=='AGAINST') { $sign = -1 ; $signDescription = _('AGAINST') ; }
            elseif ( $isPartyVote)                                       { $sign =  0 ; $signDescription = _('ABSTAIN') ; }
            if (!$isPartyVote && $jsonVotes[$voteOfSenator['senatorID']]=='FOR')         { $sign =  1 ; $splitVoteDetail['FOR'].=$voteOfSenator['name'].' ['.$voteOfSenator['votes'].$addedTalentsMessage.'], ';}
            elseif (!$isPartyVote && $jsonVotes[$voteOfSenator['senatorID']]=='AGAINST') { $sign = -1 ; $splitVoteDetail['AGAINST'].=$voteOfSenator['name'].' ['.$voteOfSenator['votes'].$addedTalentsMessage.'], ';}
            elseif (!$isPartyVote)                                                       { $sign =  0 ; $splitVoteDetail['ABSTAIN'].=$voteOfSenator['name'].' [0], ';}
            $totalVotes += ( ($voteOfSenator['votes']+$addedTalents) * $sign) ;
            // Handle unanimous vote for split votes
            if (!$isPartyVote)
            {
                // Initialise the sign of the unanimous vote to how the current Senator has voted
                if ($unanimousSign===NULL)                          { $unanimousSign=$sign ; $unanimous=TRUE; }
                // If we already knew the unanimous sign, compare it with the current senator. If it's different, this is not a unanimous vote
                if ($unanimousSign!==NULL && $unanimousSign!=$sign) { $unanimous=FALSE ; }
            }
        }
        /**
         * Description of the vote
         */
        if ($isPartyVote)
        {
            $description = (
                $sign!=0 ?
                (sprintf(_('%1$s %2$d votes %3$s%4$s') , $partyName , abs($totalVotes) , $signDescription , ($totalTalentsAdded>0 ? sprintf(_('(including %1$d T)') , $totalTalentsAdded): ''))) :
                (sprintf(_('%1$s ABSTAINS') , $partyName ))
            ) ;
            $unanimous = TRUE ;
        }
        else
        {
            if ($totalVotes>0) {$splitMessage=sprintf(_('%1$d votes FOR') , $totalVotes);}
            if ($totalVotes<0) {$splitMessage=sprintf(_('%1$d votes AGAINST') , -$totalVotes);}
            if ($totalVotes==0) {$splitMessage=sprintf(_('0 votes') , -$totalVotes);}
            $description = sprintf(_('%1$s is split for a total of %2$s (') , $partyName , $splitMessage) ;
            if ($splitVoteDetail['FOR']!='') {$description.=_(' FOR : ').substr($splitVoteDetail['FOR'],0,-2).' ; ';}
            if ($splitVoteDetail['AGAINST']!='') {$description.=_(' AGAINST : ').substr($splitVoteDetail['AGAINST'],0,-2).' ; ';}
            if ($splitVoteDetail['ABSTAIN']!='') {$description.=_(' ABSTAIN : ').substr($splitVoteDetail['ABSTAIN'],0,-2).' ; ';}
            $description = substr($description,0,-3).')';
        }
        /**
         * Finally, was there a popular appeal ?
         */
        if (array_key_exists('popularAppeal', $jsonVotes))
        {
            $appealTable = $game->getAppealTable() ;
            $roll = $game->rollDice(2, -1) ;
            $rollTotal=$roll['total'] ;
            // POP changes the roll on appeal table
            $popularityMessage = '';
            $accused = $game->getFilteredCards(array('senatorID'=>$currentProposal->getContent()['Accused']))->first() ;
            /** @todo : use victim's POP in case of assasination special prosecution */
            $popularity = $accused->getPOP() ;
            if ($popularity!=0)
            {
                $popularityMessage = sprintf(_(' %1$s %2$d popularity') , ($popularity>0 ? _('plus') : _('minus')) , abs($popularity)) ;
                $modifiedRollTotal = $rollTotal + $popularity ;
                $rollTotal=max(2 , min(12 , $modifiedRollTotal)) ;
            }
            // Appeal special effect : killed|freed
            $specialEffect = $appealTable[$rollTotal]['special'] ;
            if ($specialEffect!=NULL)
            {
                if ($specialEffect=='freed')
                {
                    $currentProposal->addContent(array('PopularAppealSpecial' => 'freed' , 'PopularAppealModifiedRoll' => $modifiedRollTotal)) ;
                    $description = _('Immediate acquital by popular appeal.') ;
                }
                elseif ($specialEffect=='killed')
                {
                    $currentProposal->addContent(array('PopularAppealSpecial'=>'killed')) ;
                    $description = _('Accused killed by the mob following popular appeal.') ;
                }
            }
            else
            {
                $appealVotes = $appealTable[$rollTotal]['votes'] ;
                $totalVotes-=$appealVotes ;
                if ($appealVotes > 0)       { $extraVotesMessage = sprintf(_('an extra %1$d votes AGAINST') , abs($appealVotes));}
                elseif  ($appealVotes < 0)  { $extraVotesMessage = sprintf(_('an extra %1$d votes FOR')     , abs($appealVotes));}
                else                        { $extraVotesMessage = _('no extra votes'); }
                $description.=sprintf(_('. Popular appeal roll is %1$d%2$s%3$s resulting in %4$s. The final tally is %5$d %6$s') , $roll['total'] , $game->getEvilOmensMessage(-1) , $popularityMessage , $extraVotesMessage , abs($totalVotes) , (($totalVotes > 0) ? _('FOR') : _('AGAINST')));
            }
        }
        $currentProposal->setVote($user_id, $totalVotes, $description , $unanimous) ;
        $game   ->log($description)
                ->recordTrace('Proposal', array('action' => 'vote'), array('proposal' => $currentProposal));
        $this->doVoteEnd($game , $currentProposal) ;
    }
    
    /**
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     */
    public function doVoteEnd($game , $proposal)
    {
        try
        {
            $proposal->getCurrentVoter() ;
        } catch (\Exception $ex) {
            // This was the last voter
            if ($ex->getCode() == \Entities\Proposal::ERROR_NO_VOTER)
            {
                /*
                 * The vote is over
                 * - Determine the outcome 'pass' or 'fail' and save it in the proposal object
                 * - Implement the proposal if it passed
                 */
                $proposal->setOutcome(($proposal->isCurrentOutcomePass() ? 'pass' : 'fail')) ;
                /*
                 * Implement the proposal (for proposals that are implemented at the end of the vote, which is not always the case
                 */
                $type = $proposal->getType() ;
                try
                {
                    /** 
                     * Calls the method $this->implementProposal{type} where type is the proposal type with its first letter capitalised 
                     * type is among : Censor , Prosecutions , recruit , commander , concession
                     */
                    $methodName = 'implementProposal'.ucfirst($type) ;
                    if (method_exists($this,$methodName))
                    {
                        $this->$methodName($game , $proposal) ;
                    }
                } catch (Exception $ex2) {
                    throw new \Exception($ex2) ;
                }
                /**
                 * @todo Handle unanimous defeat decision on the part of the HRAO
                 * - Creates a 'Unanimous defeat' proposal
                 * - It should put it as the latest proposal
                 * - Once unanimous defeat has been handled, the proposal that was interrupted must be ->incrementStep()
                 * - When checking the latest proposal underway in the Presenter, don't forget to check the latest proposal with currentStep!=='done'
                 */
                if ($proposal->isHRAOunanimousDefeat())
                {
                    $game->log(_('This was a unanimous defeat'));
                    /* @var $HRAO \Entities\Senator */
                    $HRAO = $game->getHRAO(TRUE) ;
                    /* @var $UnanimousDefeatProposal \Entities\Proposal */
                    $UnanimousDefeatProposal = new \Entities\Proposal( $HRAO->getLocation()['value']->getUser_id() , 'UnanimousDefeat',  $game , NULL ) ;
                    /** 
                     * The HRAO has no INF left and must step down
                     * The UnanimousDefeat proposal is set to pass, decision 'stepDown' to true, and step incremented (should now be 'done')
                     * We also increment the step of the proposal that was interrupted, since the UnanimousDefeat proposal is already over.
                     */
                    if ($HRAO->getINF()==0)
                    {
                        $game->log(_('The HRAO has no INF left and must step down'));
                        $UnanimousDefeatProposal->setOutcome('pass');
                        $UnanimousDefeatProposal->setDecision('stepDown', TRUE) ;
                        $UnanimousDefeatProposal->incrementStep() ;
                        $proposal->incrementStep() ;
                    }
                    $game->setNewProposal($UnanimousDefeatProposal) ;
                    $this->persistFlush($UnanimousDefeatProposal) ;
                }
                else
                {
                    $proposal->incrementStep() ;
                }
                /**
                 * Check if proposal is done. Once it is, handle the potential situations :
                 * - Repopulating Rome (the proposal sent a Senator that made the total number of Senators in Rome below 8)
                 * - Adjourn Senate (the proposal sent the HRAO away from Rome)
                 * @todo repopulating Rome
                 * @todo adjourn senate
                 */
            }
        }
        return TRUE ;
    }

    /**
     * @param int $user_id
     * @param \Entities\Game $game
     * @param type $json_data
     * @throws \Exception
     */
    public function veto($user_id , $game , $json_data)
    {
        /** @todo veto */
    }

    /**
     * @param int $user_id
     * @param \Entities\Game $game
     * @param type $json_data
     * @throws \Exception
     */
    public function decide($user_id , $game , $json_data , $entityManager=NULL)
    {
        /**
         * Do it for all choices
         */
        try {
            /* @var $currentProposal \Entities\Proposal  */
            $currentProposal = $game->getProposals()->last() ;
            if ($currentProposal->getCurrentStep()!='decision')
            {
                throw new \Exception(_('ERROR - Wrong proposal step, should be \'decision\'')) ;
            }
            if ($json_data['user_id']!=$user_id)
            {
                throw new \Exception(_('ERROR - Trying to decide for the wrong party')) ;
            }
            /**
             * Consuls - Decision on who is Rome consul and who is Field consul
             */
            if ($game->getSubPhase()=='Consuls')
            {
                // JSON: $key = card ID ; $value = 'Rome consul'|'Field consul'
                foreach ($json_data as $key=>$value)
                {
                    if ($value=='Rome Consul' || $value=='Field Consul')
                    {
                        $senator = $game->getFilteredCards(array('cardId'=>$key))->first() ;
                        if ($senator->getLocation()['value']->getUser_id()!=$user_id)
                        {
                            throw new \Exception(_('ERROR - Senator in the wrong party')) ;
                        }
                        $currentProposalCards = $currentProposal->getContent();
                        if ($currentProposalCards['First Senator']==$key)
                        {
                            $currentProposal->setDecision('First Senator', $value);
                        }
                        elseif ($currentProposalCards['Second Senator']==$key)
                        {
                            $currentProposal->setDecision('Second Senator', $value);
                        }
                        else
                        {
                            throw new \Exception(_('ERROR - Senator not part of a choice')) ;
                        }
                    }
                }
                $decision=$currentProposal->getDecision();
                /**
                 * Check whether all needed choices have been made
                 */
                if ( ($decision['First Senator']!=NULL) && ($decision['Second Senator']!=NULL) )
                {
                    /**
                     * Disagreement, choose randomely
                     */
                    if ($decision['First Senator']==$decision['Second Senator'])
                    {
                        $game->log(_('The parties could not agree.'));
                        if (rand(1,100)<=50)    { $decision['First Senator'] = 'Rome Consul' ;  $decision['Second Senator'] = 'Field Consul' ; }
                        else                    { $decision['First Senator'] = 'Field Consul' ; $decision['Second Senator'] = 'Rome Consul' ;  }
                    }
                    /**
                     * Appointments
                     */
                    // $key = 'First Senator'|'Second Senator' , $office = 'Rome consul' | 'Field consul'
                    foreach ($decision as $key=>$office)
                    {
                        $senator = $game->getFilteredCards(array('cardId'=>$currentProposal->getContent()[$key]))->first() ;
                        $this->appoint($game, $currentProposal , $senator, $office) ;
                    }
                    $currentProposal->incrementStep();
                    /**
                     * At the end of Consul agreements, jump to Dictator phase if conditions are met by $game->getDictatorPossible(), otherwise, prosecutions
                     */
                    if ($game->getDictatorPossible())
                    {
                        $game->setSubPhase('Dictator');
                    }
                    else
                    {
                        $game->log(_('There isn\'t 3 or more active wars, or one with a combined strength of 20+. No Dictator can be appointed or elected.'));
                        $this->doAutomaticCensor($game , $currentProposal) ;
                    }
                }
            }
            
            /**
             * The prosecutor can :
             * - Agree : proposal->incrementStep
             * - Disagree : delete proposal
             */
            if ($game->getSubPhase()=='Prosecutions')
            {
                $prosecutor = $game->getFilteredCards(array('senatorID'=>$currentProposal->getContent()['Prosecutor']))->first() ;
                if ($json_data['ProsecutorAgrees']=='Disagree')
                {
                    $game->log(sprintf(_('The prosecutor %1$s refuses to prosecute.') , $prosecutor->getName()));
                    $entityManager->remove($currentProposal) ;
                    $entityManager->flush() ;
                }
                elseif ($json_data['ProsecutorAgrees']=='Agree')
                {
                    $game->log(sprintf(_('The prosecutor %1$s agrees to prosecute.') , $prosecutor->getName()));
                    $currentProposal->incrementStep();
                }
                else
                {
                    throw new \Exception(_('ERROR - Unrecognised choice. Should be agree or disagree.')) ;
                }
            }
            
            /**
             * A commander without adequate forces can decide not to go fight : Step 0 is the decision 
             */
             if ( ($game->getSubPhase()=='OtherBusiness') && ($currentProposal->getType()=='commander') && ($currentProposal->getCurrentStep()==0))
             {
             	$decisionsAlreadyTaken = $currentProposal->getDecision() ;
             	$allFor = TRUE ;
                foreach ($json_data['toggles'] as $senatorID=>$decision)
                {
                	foreach ($decisionsAlreadyTaken as $decisionSenatorID => $decisionSenatorDecision)
                	{
                		// This is the Senator we are looking for
                		if ($decisionSenatorID == $senatorID)
                		{
			                $commander = $game->getFilteredCards(array('senatorID'=>$senatorID))->first() ;
                			// Had not already decided - set decisoin
                			if ($decisionSenatorDecision==NULL)
                			{
                				$currentProposal->setDecision($senatorID , $decision);
                				$decisionSenatorDecision = $decision ;
			                	// Check whether the decision was AGAINST, in which case the proposal is scrapped
			                	if ($decision=='AGAINST')
			                	{
                                                    $allFor = FALSE ;
                                                    $game   ->log(sprintf(_('%1$s refuses to go with inadequate forces.') , $commander->getName()))
                                                            ->recordTrace('Proposal', array('action' => 'decide' , 'decision' => 'AGAINST'), array('commander' => $commander));
                                                    /** 
                                                     * @todo I cannot remove the Proposal without violating the constraint on Trace, but should I set it to fail or have a 'removed' status instead ?
                                                     * Setting it to fail might trigger unseen problems with unanimous defeat or making impossible to propose the same Proposal again
                                                     */
                                                    $currentProposal->setOutcome('fail') ;
                                                    $currentProposal->setStep(2) ;
			                	}
                                                if ($decision=='FOR')
                                                {
                                                    $game   ->log(sprintf(_('%1$s agrees to go with inadequate forces.') , $commander->getName()))
                                                            ->recordTrace('Proposal', array('action' => 'decide' , 'decision' => 'FOR'), array('commander' => $commander));
                                                }
                			}
                		}
                		if ($decisionSenatorDecision==NULL)
                		{
                                    $allFor = FALSE ;
                		}
                	}
                }
                // Check whether all decision have been taken
                if ($allFor)
                {
                    $game->log(_('All commanders with inadequate forces have agreed to fight.'));
                    $currentProposal->incrementStep();
                }
             }
             
             /**
              * Unanimous defeat decision
              */
            if ( ($currentProposal->getType()=='UnanimousDefeat') && ($currentProposal->getCurrentStep()==0))
            {
                /** Lose 1 influence */
                if ($json_data['toggles']['unanimousDefeatName']=='NO')
                {
                    $HRAO = $game->getHRAO(TRUE) ;
                    $HRAO->setSteppedDown(FALSE) ;
                    $HRAO->changeINF(-1) ;
                    $game->log(sprintf(_('%1$s decides to lose 1 Influence') , $HRAO->getFullName()));
                }
                /** Step down */
                if ($json_data['toggles']['unanimousDefeatName']=='YES')
                {
                    $HRAO = $game->getHRAO(TRUE) ;
                    $HRAO->setSteppedDown(TRUE) ;
                    $newHRAO = $game->getHRAO(TRUE) ;
                    $game->log(sprintf(_('%1$s decides to step down as presiding magistrate. %2$s is the new president.') , $HRAO->getFullName() , $newHRAO->getFullName()));
                }
                $currentProposal->incrementStep();

                /**
                 * Also Increment step of the proposal that was interrupted
                 */
                foreach($game->getProposals() as $proposal)
                {
                    if ( ($proposal->getCurrentStep()!='done') && $proposal->getType()!='UnanimousDefeat')
                    {
                        /* @var $currentProposal \Entities\Proposal  */
                        $previousProposal = $proposal ;
                    }
                }
                if (isset($previousProposal))
                {
                    $previousProposal->incrementStep();
                }
                
            }
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
    }

    /**
     * Disappoint current official, and appoints new one. Throws exceptions left and right.
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     * @param \Entities\Senator $senator
     * @param string $office
     * @throws \Exception
     */
    public function appoint($game , $proposal , $senator , $office)
    {
        try {
            /* @var $currentOfficial \Entities\Senator  */
            $currentOfficial =  $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'is'.$office)->first() ;
            if ($currentOfficial!=NULL)
            {
                $currentOfficial->setOffice(NULL) ;
            }
        } catch (\Exception $ex) {
            throw new \Exception(_('The following error was returned when retrieving the current official (if any) : ').$ex->getTraceAsString()) ;
        }
        try {
            $senator->appoint($office) ;
            $game   ->log(sprintf(_('%1$s becomes %2$s') , $senator->getName() , $office))
                    ->recordTrace('Proposal', array('action' => 'appoint' , 'office' => $office), array('proposal' => $proposal , 'senator' => $senator));
        } catch (\Exception $ex) {
            throw new \Exception(_('The following error was returned when appointing a Senator : ').$ex->getTraceAsString()) ;
        }
    }
    
    /**
     * If there is only one possible Censor, appoint him and move to Prosecutions, otherwise move to Censor election
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     */
    public function doAutomaticCensor($game , $proposal)
    {
        $game->setSubPhase('Censor');
        $listOfPossibleCensors  = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleCensor') ;
        if (count($listOfPossibleCensors)==1)
        {
            $newCensor = $listOfPossibleCensors->first() ;
            $game->log(sprintf(_('%1$s is the only possible Censor. He is automatically elected.') , $newCensor->getName()));
            $this->appoint($game, $proposal , $newCensor, 'Censor') ;
            $game->setSubPhase('Prosecutions');
        }
    }
    
    /**
     * Implements a prosecution :
     * - First, if there is a 'PopularAppealSpecial' in the proposal's content, deal with it : killed|freed
     * - Otherwise, check votes
     * - Implement effects for conviction :
     * > Major : accused killed
     * > Minor : -5 POP , -5 INF , -Prior consul , lose concessions
     * > both : prosecutor +1/2 INF of accused (INF lost capped at 5) , +Prior consul
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     */
    public function implementProposalProsecutions($game , $proposal)
    {
        $content = $proposal->getContent() ;
        $censor = $game->getAllSenators('isCensor')->first() ;
        $prosecutor = $game->getFilteredCards(array('senatorID'=>$proposal->getContent()['Prosecutor']))->first() ;
        $accused = $game->getFilteredCards(array('senatorID'=>$proposal->getContent()['Accused']))->first() ;
        /**
         * Popular appeal special result
         */
        if (array_key_exists('PopularAppealSpecial' , $content))
        {
            if ($content['PopularAppealSpecial']=='freed')
            {
                $modifiedRoll = (int)$content['PopularAppealModifiedRoll'] ;
                $numberOfChits = $modifiedRoll - 11 ;
                $game->log(sprintf(_('An angry mob targets the Censor and Prosecutor. With a modified roll of %1$d, %1$d%2$s') , $modifiedRoll , $numberOfChits , ($numberOfChits > 1 ? _(' mortality chit is drawn.') : _(' mortality chits are drawn.'))));
                $chits = $game->mortality_chits($numberOfChits) ;
                foreach ($chits as $chit)
                {
                    if ($chit!='NONE' && $chit!='DRAW 2')
                    {
                        // Can only kill the Censor or Prosecutor : use the 'mob' array of killSenator()
                        $returnedMessage = $game->killSenator($chit , FALSE , FALSE , FALSE , FALSE , array(1=>$censor->getSenatorID() , 2=>$prosecutor->getSenatorID())) ;
                        $game->log 
                        (
                            _('Chit drawn : %1$s. %2$s') ,
                            (isset($returnedMessage[1]) ? $returnedMessage[1] : 'log') ,
                            array($chit , $returnedMessage[0])
                        );
                    }
                    else
                    {
                        $game->log(_('Chit drawn : %1$s') , 'log' , array($chit)) ;
                    }
                }
            }
            elseif ($content['PopularAppealSpecial']=='killed')
            {
                $game->log(_('The populace is so disgusted by the self-serving rhetoric of the accused that they kill him themselves'));
                $returnedMessage = $game->killSenator($accused->getSenatorID() , TRUE) ;
                $game->log($returnedMessage[0]) ;
            }
            else
            {
                throw new \Exception(_('ERROR - Unrecognised popular appeal special result.')) ;
            }
        }
        $prosecutionType = $proposal->getContent()['Type'] ;
        /**
         * Convicted
         */
        if ($proposal->isCurrentOutcomePass())
        {
            $lostINF = min($accused->getINF() , 5) ;
            $lostPOP = min(9+$accused->getPOP() , 5) ;
            $lostPriorConsul = $accused->getPriorConsul() ;
            $game->log(_('The accused is convicted.')) ;
            if ($prosecutionType=='Major')
            {
                $returnedMessage = $game->killSenator($accused->getSenatorID() , TRUE) ;
                $game->log($returnedMessage[0]) ;
            }
            else
            {
                $accused->changeINF(-5) ;
                $accused->changePOP(-5) ;
                $accused->setPriorConsul(FALSE) ;
                $message=sprintf(_('He loses %1$d influence, %2$d popularity%3$s.') , $lostINF , $lostPOP, ($lostPriorConsul ? _(' and his prior consul marker') : ''));
                if ($accused->hasControlledCards())
                {
                    $concessionsName = '' ;
                    foreach($accused->getCardsControlled()->getCards() as $card)
                    {
                        if ($card->getPreciseType()=='Concession')
                        {
                            $card->setCorrupt(FALSE);
                            $accused->getCardsControlled()->getFirstCardByProperty('cardId' , $card->getCardId() , $game->getDeck('forum')) ;
                            $concessionsName.=$card->getName().', ';
                        }
                    }
                    if ($concessionsName!='')
                    {
                        $message.=sprintf(sprintf(_(' He also loses his concessions which are returned to the forum : %1$s') , substr($concessionsName,0,-2)));
                    }
                }
                $game->log($message) ;
            }
            /**
             * Prosecutor gains
             */
            $INFgained=(int)($lostINF/2) ;
            $prosecutor->changeINF($INFgained) ;
            $prosecutor->setPriorConsul(TRUE) ;
            $game->log(sprintf(_('The prosecutor gains %1$d influence and a prior consul marker.') , $INFgained));
        }
        /**
         * Acquited
         */
        else
        {
            $game->log(_('The accused is acquited')) ;
        }
        /**
         * Move on to the next phase if no more prosecutions are possible
         */
    }

    /**
     * Implements OtherBusiness - recruit
     * proposal content is fleetsToRecruit , regularsToRecruit
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     *
     */
    public function implementProposalRecruit($game , $proposal)
    {
        $fleetsToRecruit = $proposal->getContent()['fleetsToRecruit'] ;
        $fleetStatus = $game->getFleetStatus() ;
        if ($fleetStatus['canBeRecruited'] < $fleetsToRecruit)
        {
            throw new \Exception(_('ERROR - Not enough fleet to recruit.')) ;
        }
        $regularsToRecruit = $proposal->getContent()['regularsToRecruit'] ;
        $legionStatus = $game->getLegionsStatus() ;
        if ($legionStatus['regularsCanBeRecruited'] < $regularsToRecruit)
        {
            throw new \Exception(_('ERROR - Not enough regulars to recruit.')) ;
        }
        if ( ( ($regularsToRecruit * $legionStatus['cost']) + ($fleetsToRecruit * $fleetStatus['cost']) ) > $game->getTreasury() )
        {
            throw new \Exception(_('ERROR - Not enough money in Rome treasury.')) ;
        }
        // Implement if the proposal passes
        if ($proposal->isCurrentOutcomePass())
        {
            $game->changeTreasury( - ( ($regularsToRecruit * $legionStatus['cost']) + ($fleetsToRecruit * $fleetStatus['cost']) )) ;
            $game->log(_('The forces are recruited'));
            // Ship building
            $shipBuilding = $game->getFilteredCards(array('special' => 'fleets'))->first() ;
            $shipBuildingLocation = $shipBuilding->getLocation() ;
            if ( ($shipBuildingLocation['type'] == 'card') && ($fleetsToRecruit>0) )
            {
                $senator = $shipBuildingLocation['value'] ;
                $senator->changeTreasury(3 * $fleetsToRecruit);
                $shipBuilding->setCorrupt(TRUE);
                $game->log(sprintf(_('With ship building, %1$s earns %2$d T.') , $shipBuildingLocation['name'] , (3 * $fleetsToRecruit) ));
            }
            // Armaments
            $armament = $game->getFilteredCards(array('special' => 'legions'))->first() ;
            $armamentLocation = $armament->getLocation() ;
            if ( ($armamentLocation['type'] == 'card') && ($regularsToRecruit>0) ) 
            {
                $senator = $armamentLocation['value'] ;
                $senator->changeTreasury(2 * $regularsToRecruit);
                $armament->setCorrupt(TRUE);
                $game->log(sprintf(_('With armament, %1$s earns %2$d T.') , $armamentLocation['name'] , (2 * $regularsToRecruit) ));
            }
            $fleet = 0 ;
            while (($fleetsToRecruit>0) && ($fleet<25))
            {
                $thisFleet = $game->getFleets()[$fleet] ;
                if ($thisFleet->canBeRecruited())
                {
                    $thisFleet->recruit() ;
                    $fleetsToRecruit-- ;
                }
                $fleet++;
            }
            $legion = 0 ;
            while (($regularsToRecruit>0) && ($legion<25))
            {
                $thisLegion = $game->getLegions()[$legion] ;
                if ($thisLegion->canBeRecruited())
                {
                    $thisLegion->recruit() ;
                    $regularsToRecruit-- ;
                }
                $legion++;
            }
        }

    }
    
    /**
     * 
     * Implements OtherBusiness - commander
     * proposal content is commander, conflict , fleets, regulars 
     * @todo : veterans
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     *
     */
    public function implementProposalCommander($game , $proposal)
    {
    	// A commander proposal consists of an array, since they can be grouped.
    	foreach ($proposal->getContent() as $sendForces)
    	{
    		/** @todo Check constraints : 
                 * - commander has agreed
                 * - conflict can be attacked
                 * - there are enough fleets, regulars, and veterans
                 * - there are  enough support fleets 
                 */
    		
    		/** @todo : $sendForces['veterans'] ;  */

    		// Implementing the proposal itself
                /* @var $commander \Entities\Senator */
                /* @var $conflict \Entities\Conflict */
    		$commander = $game->getFilteredCards(array('senatorID'=>$sendForces['commander']))->first() ;
    		$conflict = $game->getFilteredCards(array('cardId'=>$sendForces['conflict']))->first() ;

                // Setting 'commanderIn' for the Senator (as well as the Master of Horse if sending a Dictator
                $commander->setCommanderIn($conflict) ;
                if ($commander->getOffice()=='Dictator')
                {
                    $MoH = $game->getFilteredCards(array('office'=>'Master of Horse'))->first() ;
                    $MoH->setCommanderIn($conflict) ;
                }

    		// Set fleets location
                $fleetsToSend = $sendForces['fleets'] ;
                foreach($game->getFleets() as $fleet)
                {
                    // Find the first fleet in Rome
                    if ( ($fleetsToSend>0) && ($fleet->getLocation() == 'Rome'))
                    {
                        /** @var \Entities\Fleet $fleet  */
                        $fleet->setLocation($conflict);
                        $fleetsToSend--;
                    }
                }
                
                // Set regulars location
                $regularsToSend = $sendForces['regulars'] ;
                foreach ($game->getLegions() as $legion)
                {
                    // Find the first regular in Rome
                    if ( ($regularsToSend>0) && (!$legion->getVeteran()) && ($legion->getLocation() == 'Rome'))
                    {
                        /* @var $legion \Entities\Legion */
                        $legion->setLocation($conflict);
                        $regularsToSend--;
                    }
                }
                /** @todo implement commander proposal : Set veterans */
    	}
        /** @todo Check if the President has been sent to war, in which case give a chance to leave th Senate open */
    }
    
    /**
     * Implements OtherBusiness - concession proposal 
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     * @throws \Exception
     */
    public function implementProposalConcession($game , $proposal)
    {
    	$game->log(print_r($proposal->getContent(),TRUE));
        try
        {
            // A concession proposal consists of an array, since they can be grouped.
            foreach ($proposal->getContent() as $concessionAssignment)
            {
                /* @var $senator \Entities\Senator */
                $senator = $game->getFilteredCards(array('senatorID'=>$concessionAssignment['senator']))->first() ;
                if ($proposal->isCurrentOutcomePass())
                {
                    $game->getDeck('forum')->getFirstCardByProperty('cardId', $concessionAssignment['concession'], $senator->getCardsControlled()) ;
                }
                else
                {
                    $game->getDeck('forum')->getFirstCardByProperty('cardId', $concessionAssignment['concession'])->setFlipped(TRUE) ;
                }
            }
            // Message
            if ($proposal->isCurrentOutcomePass())
            {
                $game->log(_('Vote passes - The concessions are assigned'));
            }
            else
            {
                $game->log(_('Vote fails - The concessions are flipped for the reminder of this Senate phase'));
            }
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
    }

    /**
     * find and set the next Senate subPhase base on the grand scheme of things
     * @param \Entities\Game $game
     */
    public function setNextSubPhase($game)
    {
        if ($game->getSubPhase()=='Prosecutions')
        {
            if (count($game->getListOfAvailableProvinces())==0)
            {
                $game->log(_('There are no governorships available'));
                $game->setSubPhase('OtherBusiness') ;
            }
            else
            {
                $game->setSubPhase('Governors') ;
            }
        }
    }
}
