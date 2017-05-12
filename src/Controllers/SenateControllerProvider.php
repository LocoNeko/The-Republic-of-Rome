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
                    return $app->redirect($app['BASE_URL'].'/Lobby/List') ;
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
                /** @var \Entities\Proposal $proposal */
                $proposal = $this->makeProposal($user_id , $game , $json_data);
                $game->setNewProposal($proposal) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                // TO DO : remove the alert below once happy
                $app['session']->getFlashBag()->add('danger', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
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
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                // TO DO : remove the alert below once happy
                $app['session']->getFlashBag()->add('danger', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
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
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
        })
        ->bind('verb_senateVeto');

        /*
        * POST target
        * Verb : senateAgree
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/senateAgree', function($game_id , Request $request) use ($app)
        {
            try
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $app['session']->getFlashBag()->add('danger', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                $this->agree($user_id , $game , $json_data) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            } catch (\Exception $exception) {
                // TO DO : remove the alert below once happy
                $app['session']->getFlashBag()->add('danger', ' Received json : '.json_encode($json_data, JSON_PRETTY_PRINT));
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201 );
            }
        })
        ->bind('verb_senateAgree');
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
            $game->log($proposal->getDescription(), 'log') ;
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
                throw new \Exception(_('Current voter and user mismatch')) ;
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
        $currentProposal->setVote($user_id, $totalVotes, $description) ;
        $game->log($description);
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
                 * - Determine the outcome 'pass' or 'fail'
                 * - Implement the proposal if it passed
                 */
                $proposal->setOutcome(($proposal->isCurrentOutcomePass() ? 'pass' : 'fail')) ;
                // TO DO : describe outcome
                $proposal->incrementStep() ;
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
        // TO DO
    }

    /**
     * @param int $user_id
     * @param \Entities\Game $game
     * @param type $json_data
     * @throws \Exception
     */
    public function agree($user_id , $game , $json_data)
    {
        /**
         * TO DO : This is only for consuls. Do it for all choices
         */
        try {
            /* @var $currentProposal \Entities\Proposal  */
            $currentProposal = $game->getProposals()->last() ;
            if ($currentProposal->getCurrentStep()!='agree')
            {
                throw new \Exception(_('ERROR - Wrong proposal step, should be \'agree\'')) ;
            }
            // $agreeCurrent = 'Rome consul'|'Field consul' = cardId
            $agreeCurrent=$currentProposal->getAgree();
            if ($json_data['user_id']!=$user_id)
            {
                throw new \Exception(_('ERROR - Trying to agree with the wrong party')) ;
            }
            // JSON: $key = card ID ; $value = 'Rome consul'|'Field consul'
            foreach ($json_data as $key=>$value)
            {
                if ($value=='Rome Consul' || $value=='Field Consul')
                {
                    $senator = $game->getFilteredCards(array('id'=>$key))->first() ;
                    if ($senator->getLocation()['value']->getUser_id()!=$user_id)
                    {
                        throw new \Exception(_('ERROR - Senator in the wrong party')) ;
                    }
                    $currentProposalCards = $currentProposal->getCards();
                    if ($currentProposalCards['First Senator']==$key)
                    {
                        $currentProposal->setAgree('First Senator', $value);
                    }
                    elseif ($currentProposalCards['Second Senator']==$key)
                    {
                        $currentProposal->setAgree('Second Senator', $value);
                    }
                    else
                    {
                        throw new \Exception(_('ERROR - Senator not part of a choice')) ;
                    }
                }
            }
            $agreeUpdated=$currentProposal->getAgree();
            /**
             * Check whether all needed choices have been made
             */
            if ( ($agreeUpdated['First Senator']!=NULL) && ($agreeUpdated['Second Senator']!=NULL) )
            {
                /**
                 * Disagreement, choose randomely
                 */
                if ($agreeUpdated['First Senator']==$agreeUpdated['Second Senator'])
                {
                    $game->log(_('The parties could not agree.'));
                    if (rand(1,100)<=50)
                    {
                        $agreeUpdated['First Senator'] = 'Rome Consul' ; 
                        $agreeUpdated['Second Senator'] = 'Field Consul' ;
                    }
                    else
                    {
                        $agreeUpdated['First Senator'] = 'Field Consul' ; 
                        $agreeUpdated['Second Senator'] = 'Rome Consul' ;
                    }
                }
                /**
                 * Appointments
                 */
                // $key = 'First Senator'|'Second Senator' , $office = 'Rome consul' | 'Field consul'
                foreach ($agreeUpdated as $key=>$office)
                {
                    $senator = $game->getFilteredCards(array('id'=>$currentProposal->getCards()[$key]))->first() ;
                    $this->appoint($game, $senator, $office) ;
                }
                $currentProposal->incrementStep();
                if ($game->getDictatorPossible())
                {
                    $game->setSubPhase('Dictator');
                }
                else
                {
                    $game->log(_('There isn\'t 3 or more active wars, or one with a combined strength of 20+. No Dictator can be appointed or elected.'));
                    $this->doAutomaticCensor($game) ;
                }
            }
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
    }

    /**
     * Disappoint current official, and appoints new one. Throws exceptions left and right.
     * @param \Entities\Game $game
     * @param \Entities\Senator $senator
     * @param string $office
     * @throws \Exception
     */
    public function appoint($game , $senator , $office)
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
            $game->log(sprintf(_('%1$s becomes %2$s') , $senator->getName() , $office));
        } catch (\Exception $ex) {
            throw new \Exception(_('The following error was returned when appointing a Senator : ').$ex->getTraceAsString()) ;
        }
    }
    
    /**
     * If there is only one possible Censor, appoint him and move to Prosecutions, otherwise move to Censor election
     * @param \Entities\Game $game
     */
    public function doAutomaticCensor($game)
    {
        $game->setSubPhase('Censor');
        $listOfPossibleCensors  = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleCensor') ;
        if (count($listOfPossibleCensors)==1)
        {
            $newCensor = $listOfPossibleCensors->first() ;
            $game->log(sprintf(_('%1$s is the only possible Censor. He is automatically elected.') , $newCensor->getName()));
            $this->appoint($game, $newCensor, 'Censor') ;
            $game->setSubPhase('Prosecutions');
        }
    }
}
