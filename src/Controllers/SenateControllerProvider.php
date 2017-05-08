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
                    $app['session']->getFlashBag()->add('danger', print_r($exception->getTrace(),TRUE));
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

                //$this->entityManager->persist($game);
                //$this->entityManager->flush();
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
        error_log('HIP');
        try
        {
            $proposal->getCurrentVoter() ;
        } catch (\Exception $ex) {
            error_log('HOP');
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
        try {
            /* @var $currentProposal \Entities\Proposal  */
            $currentProposal = $game->getProposals()->last() ;
            if ($currentProposal->getCurrentStep()!='agree')
            {
                throw new \Exception(_('ERROR - Wrong proposal step, should be \'agree\'')) ;
            }
            $agreeCurrent=$currentProposal->getAgree();
            foreach ($json_data as $key=>$value)
            {
                if ($value=='Rome consul')
                {
                    if ($agreeCurrent['Rome consul']!=NULL)
                    {
                        throw new \Exception(_('ERROR - There is already a Rome Consul')) ;
                    }
                }
                elseif ($value=='Field consul')
                {
                    if ($agreeCurrent['Field consul']!=NULL)
                    {
                        throw new \Exception(_('ERROR - There is already a Field Consul')) ;
                    }
                }
            }
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
    }

}
