<?php
namespace Presenters ;
use Doctrine\Common\Collections\ArrayCollection;

// TO DO : Beginning of Senate phase
//  - Initialise free tribunes of all senators
//  - Initialise assassination attempts and targets
class SenatePhasePresenter
{
    // Common to all phase presenters
    public $user_id;
    public $game;
    public $yourParty;
    public $otherParties = [];
    public $header = [];
    public $interface = [];
    public $sliders = [];
    public $data_json ;
    
    /**
     * @param \Entities\Game $game
     * @param int $user_id
     * @throws \Exception
     */
    public function __construct($game, $user_id)
    {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id;
        /** @todo This is a gamePresenter, should be called that ! see Issue #50 */
        $this->game = new \Presenters\GamePresenter($game, $user_id);
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id) 
            {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id);
            }
            else
            {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id);
            }
        }
        
        /**
         * Phase Header
         */
        $this->header['list'] = array();
        $this->header['actions'] = array();
        $this->header['description'] = _('Senate') ;
        if ($game->getPhase() != 'Senate') 
        {
            throw new \Exception(_('ERROR - Wrong phase')) ;
        }
        /*
        * Show Assassination button if the party hasn't made an attempt yet
        */
        if ($game->getParty($user_id)->getAssassinationAttempt() === FALSE)
        {
            $this->header['actions'][] = array(
                'type'=> 'button' ,
                'verb' => 'senateAssassinate' ,
                'style' => 'danger' ,
                'text'=> _('ATTEMPT ASSASSINATION')
            ) ;
        }

        /**
         * There is a proposal underway
         */
        /**
         * @todo : A proposal can be interrupted in the following cases :
         *
         * - Unanimous defeat
         * - Assassination
         * - Special assassination prosecution
         * - Repopulating Rome
         */
        /**
         * Look through all proposals that are not done, this should get the latest one
         */
        foreach($game->getProposals() as $proposal)
        {
            if ($proposal->getCurrentStep()!='done')
            {
                /* @var $currentProposal \Entities\Proposal  */
                $currentProposal = $proposal ;
            }
        }
        /**
         * There's a proposal underway
         */
        if (isset($currentProposal) && $currentProposal->getCurrentStep()!='done')
        {
            $this->header['description'] .= _(' - Proposal underway');
            $this->header['list'] = array (
                $this->game->displayContextualName($game->getProposals()->last()->getDescription() , $user_id)
            );
            /**
             * This proposal's current step is 'vote'
             */
            if ($currentProposal->getCurrentStep()=='vote')
            {
                /*
                 * Describe the votes so far (if any)
                 */
                foreach ($currentProposal->getVote() as $voteOfParty)
                {
                    if ($voteOfParty['votes']!=NULL)
                    {
                        $this->header['list'][] = $voteOfParty['description'];
                    }
                }
                try 
                {
                    $votingOrWaiting = $currentProposal->getVotingOrWaiting($user_id) ;
                } catch (Exception $ex) {
                    throw new \Exception(_('WRONG PROPOSAL - ').$ex->getMessage()) ;
                }
                $this->header['list'][] = sprintf(_('The proposal would currently %1$s') , ($currentProposal->isCurrentOutcomePass() ? _('PASS') : _('FAIL'))) ;
                $this->header['list'][] = $votingOrWaiting['message'] ;
                /**
                 * Waiting for another party to vote
                 */
                if ($votingOrWaiting['state'] == 'waiting')
                {
                    // TO DO
                    // But what do I really need to do here ???
                }
                /**
                 * Voting interface (I could check for 'state' == 'voting', but why should I ? exception has been caught before)
                 */
                else
                {
                    /* 
                     * - A list of Senators reflecting the YES/NO of the general switch, but that can be overriden. Each Senator also has a treasury drop down
                     * - An optional "Popular appeal"
                     * - A VETO button with a "with" dropdown (Tribune, Free tribune, Free veto...)
                     * - A VOTE button
                     */
                    $this->interface['name'] = 'senateVote';
                    // General toggle to vote FOR/AGAINST/ABSTAIN as a whole party
                    $this->interface['senateGeneralVote'] =  array (
                        'type'  => 'toggle' ,
                        'name' => 'partyVote' ,
                        'class' => 'togglePartyVote' ,
                        'items' => array(
                            array('value' => 'FOR' , 'description' =>_('FOR')) ,
                            array('value' => 'AGAINST' , 'description' =>_('AGAINST')) ,
                            array('value' => 'ABSTAIN' , 'description' =>_('ABSTAIN'))
                        ) ,
                        'default' => 'ABSTAIN'
                    ) ;
                    // Popular appeal
                    if (array_key_exists ( 'Accused' , $currentProposal->getContent()) && $this->canUsePopularAppeal($game , $currentProposal , $user_id))
                    {
                        $this->interface['senatePopularAppeal'] =  array (
                            'type'  => 'toggle' ,
                            'name' => 'popularAppeal' ,
                            'class' => 'togglePopularAppeal' ,
                            'items' => array(
                                array('value' => 'YES' , 'description' =>_('YES')) ,
                                array('value' => 'NO' , 'description' =>_('NO'))
                            ) ,
                            'default' => 'NO'
                        ) ;
                        // TO DO : Add an option to use Veto if popular appeal fails
                    }
                    // List of Senators able to vote : name, votes, tooltip to explain (ORA, knights, INF in some cases...) , optional dropdown to spend talents , override of FOR/AGAINST/ABSTAIN
                    $this->interface['senateVoteSenators'] = $currentProposal->getVoteTally($user_id) ;
                    // Vote button
                    $this->interface['senateVote'] = array (
                        'type' => 'button' ,
                        'verb' => 'senateVote' ,
                        'style'=> 'danger' ,
                        'text' => _('VOTE')
                    ) ;
                    // Vetoes (Tribune cards, Free tribunes, Free veto
                    $vetoes = array_merge(array_merge([] , $this->getCardTribunes($game->getParty($user_id))) , $this->getFreeTribunes($game->getParty($user_id))) ;
                    if (count($vetoes)>0)
                    {
                        $this->interface['senateVeto'] = array (
                            'type' => 'button' ,
                            'verb' => 'senateVeto' ,
                            'text' => _('VETO')
                        ) ;
                        $this->interface['senateVetoes'] =  array (
                            'type'  => 'select' ,
                            'class' => 'senateVetoWith' ,
                            'items' => $vetoes
                        ) ;
                    }
                    else
                    {
                        $this->interface['senateVeto'] = [] ;
                    }
                }
            }
            /**
             * This proposal's current step is 'decision'
             */
            if ($currentProposal->getCurrentStep()=='decision') 
            {
                $this->setDecisionContent($game , $currentProposal , $user_id) ;
            }
        }
        /**
         * No proposal underway
         * - Check if the user can make a proposal
         * - This will bring up the 'senateMakeProposal' interface
         * - In case he can't, this will bring up a waiting screen
         */
        else
        {
            $this->header['description'] .= _(' - No proposal underway') ;
            $listProposalHow = $this->getProposalHow($game) ; 
            
            /**
             * The player cannot propose anything anymore because :
             * - He was the President & has adjourned the Senate
             * - He was not the President, but the President has adjourned the Senate and the player didn't keep it open (by choice or because he had no way to put forth a proposal)
             */
            if ($game->getParty($user_id)->getIsDone())
            {
                if ((int)$game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $user_id)
                {
                    $this->header['list'] = array (
                        _('You have adjourned the Senate') ,
                        _('Waiting on other players to decide to keep it open')
                    ) ;
                }
                else
                {
                    $this->header['list'] = array (
                        _('The President has adjourned the Senate') ,
                        _('You couldn\'t or didn\'t want to keep it open')
                    ) ;
                }
            }
            /**
            * This user has at least one way of making a proposal
            **/
            elseif (count($listProposalHow)>0)
            {
                // Bring up the senateMakeProposal interface
                $this->interface['name'] = 'senateMakeProposal';

                /**
                * How the Proposal is made :
                * Items consists of arrays with 'type' , 'code', and 'description'
                * Types : office, statesman, tribune
                * Code : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , {senatorID} , {cardID}
                **/
                /** @todo: rethink the codes for senators & cards. They could both be card IDs : then if the card is a Senator, it's a free tribune, otherwise it's a tribune card */
                $this->interface['listProposalHow'] =  array (
                    'type'  => 'select' ,
                    'class' => 'senateMakeProposal' ,
                    'items' => $listProposalHow
                ) ;
                /**
                 * Voting order
                 */
                $this->interface['listVotingOrder'] =  array (
                    'type'  => 'sortable' ,
                    'class' => 'senateListVotingOrder' ,
                    'items' => $this->getVotingOrder($game)
                ) ;
                $this->interface['senateMakeProposal'] = array (
                    'type' => 'button' ,
                    'verb' => 'senateMakeProposal' ,
                    'text' => _('MAKE PROPOSAL')
                ) ;
                
                /**
                * The proposal's content, specific to the subPhase. This should include :
                * - A description in the header
                * - An interface proposalType
                **/
                $this->setContent($game) ;
                /*
                * Adjourn the Senate - Only for the President during the OtherBusinss phase
                 * Getting it out of the setContent function so I don't need to pass $user_id to it
                */
               if ( ($game->getSubPhase()=='OtherBusiness') && ((int)$game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $user_id) )
               {
                   $this->interface['adjournSenate']= array(
                       'type'=> 'button' ,
                       'verb' => 'senateAdjourn' ,
                       'style' => 'warning' ,
                       'text'=> _('ADJOURN')
                   ) ;
               }
            }

            /**
            * This user cannot make a proposal at this point in time
            **/
            else
            {
                $this->header['list'] = array (
                    _('You have no way to make a proposal at the moment') ,
                    _('Waiting for player who do')
                ) ;
            }
        }
    }
    
    /**
    * Function to add classes and attributes to cards
    * Not used at the moment, as proposals will be done by drop-downs
    **/
    
    public function addClassToCards($toAdd , $cardsToApplyItTo)
    {
        /**
         * Create a collection of all cards from game decks & parties
         * Hands and not included
         */
        $allCards = new ArrayCollection() ;
        foreach ($this->game->deck as $deck)
        {
            foreach ($deck->cards as $card)
            {
                $allCards->add($card) ;
            }
        }
        foreach ($this->otherParties as $party)
        {
            foreach ($party->senators as $senator)
            {
                $allCards->add($senator) ;
            }
        }
        foreach ($this->yourParty->senators as $senator)
        {
            $allCards->add($senator) ;
        }
        /**
         * Goes through the collection and checks if the target card's ID matches one of the cardsToApplyItTo
         */
        foreach ($allCards as $card)
        {
            foreach ($cardsToApplyItTo as $targetCard)
            {
                if ($targetCard->getCardId() == $card->id)
                {
                    foreach ($toAdd as $value)
                    {
                        if ($value['type'] == 'class')
                        {
                            $card->addClass($value['name']) ;
                        }
                        elseif ($value['type'] == 'attribute')
                        {
                            $card->addAttribute($value['name'] , $value['value']) ;
                        }
                    }
                }
            }
        }
    }
    /**
     * Sets the senateMakeProposal interface based on the subPhase :
     * Consuls , DictatorAppointment , Prosecutions ...
     * @param \Entities\Game $game
     * @return array
     */
    public function setContent($game)
    {
        /**
        * Consuls
        */
        if ($game->getSubPhase()=='Consuls')
        {
            $this->header['list'] = array (
                _('Consuls Election') ,
                _('You must select a pair of Senators') ,
                _('Only Senators aligned in Rome can be proposed') ,
                _('Among officials, only the Censor and Master of Horse can be proposed') ,
                _('Already rejected pairs cannot be proposed again')
            ) ;
            $this->interface['proposalType']= 'Consuls' ;
            $possibleValues = $this->getListOfCandidates($game) ;
            $this->interface['senators']= array
            (
                array (
                    'description' => _('First Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'First Senator',
                        'items' => $possibleValues 
                    )
                ) ,
                array (
                    'description' => _('Second Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Second Senator',
                        'items' => $possibleValues 
                    )
                )
            ) ;
        }
        /**
        * Dictator
        */
        elseif ($game->getSubPhase()=='Dictator')
        {
            $this->header['list'] = array (
                _('Dicator appointment') ,
            ) ;
            $this->interface['proposalType']= 'Dictator' ;
            /**
             * @todo List candidates for dictatorship
            $this->interface['senators']= array
            (
		    'description' => _('Senator') ,
		    'list' => array (
		        'type' => 'select' ,
		        'class' => 'Senator',
		        'items' => $this->getListOfCandidates($game) 
		    )
            ) ;
             * 
             */
        }
        /**
        * Censor
        */
        elseif ($game->getSubPhase()=='Censor')
        {
            $this->header['list'] = array (
                _('Censor Election') ,
                _('You must select a Senator') ,
                _('Only Senators aligned in Rome who are prior consuls can be proposed') ,
                _('The Censor can suceed himself') ,
                _('Already rejected senators cannot be proposed again')
            ) ;
            $this->interface['proposalType']= 'Censor' ;
            $this->interface['senators']= array
            (
		    'description' => _('Senator') ,
		    'list' => array (
		        'type' => 'select' ,
		        'class' => 'Senator',
		        'items' => $this->getListOfCandidates($game) 
		    )
            ) ;
        }
        /**
        * Prosecutions
        */
        elseif ($game->getSubPhase()=='Prosecutions')
        {
            $this->header['list'] = array (
                _('Prosecution') ,
                _('You must select a Senator')
            ) ;
            $whichProsecutionPossible = $game->getWhichProsecutionPossible() ;
            if ($whichProsecutionPossible=='Major')
            {
                $this->header['list'][] = _('Only Senators aligned in Rome who have a major or minor corruption marker can be prosecuted') ;
            }
            if ($whichProsecutionPossible=='Minor')
            {
                $this->header['list'][] = _('There has already been one minor prosecution') ;
                $this->header['list'][] = _('Only Senators aligned in Rome who have a minor corruption marker can be prosecuted') ;
            }
            $this->interface['proposalType']= 'Prosecutions' ;
            $this->interface['Prosecution']= array
            (
                'description' => _('Prosecution') ,
                'list' => array (
                    'type' => 'select' ,
                    'class' => 'Prosecution',
                    'items' => $this->getListOfCandidates($game , FALSE , TRUE)
                )
            ) ;
            $this->interface['Prosecutor']= array
            (
                'description' => _('Prosecutor') ,
                'list' => array (
                    'type' => 'select' ,
                    'class' => 'Prosecutor',
                    'items' => $this->getListOfCandidates($game , TRUE)
                )
            ) ;
            $this->interface['endProsecutions'] = array (
                'type' => 'button' ,
                'verb' => 'endProsecutions' ,
                'style'=> 'info' ,
                'text' => _('END PROSECUTIONS')
            ) ;

        }
        /**
        * Governors
        */
        elseif ($game->getSubPhase()=='Governors')
        {
            $this->header['list'] = array (
                _('Governors') ,
                _('You must pair Senators with provinces')
            ) ;
            $this->interface['proposalType']= 'Governors' ;
            $this->interface['senators']= array
            (
                'description' => _('Senator') ,
                'list' => array (
                    'type' => 'select' ,
                    'class' => 'Senator',
                    'items' => $this->getListOfCandidates($game)
                )
            ) ;
            $this->interface['provinces']= array
            (
                'description' => _('Province') ,
                'list' => array (
                    'type' => 'select' ,
                    'class' => 'Province',
                    'items' => $this->getListOfAvailableCards($game)
                )
            ) ;
        }
        /**
        * Other business
        */
        elseif ($game->getSubPhase()=='OtherBusiness')
        {
            $this->header['list'] = array (
                _('Other business') ,
                _('You must first choose a type of proposal')
            ) ;
            if ((int)$game->getHRAO(TRUE)->getLocation()['value']->getIsDone())
            {
                $this->header['list'][] = _('The President has adjourned the Senate and this will be the last proposal');
                $this->interface['agreeToAdjournSenate']= array(
                    'type'=> 'button' ,
                    'verb' => 'letSenateAdjourn' ,
                    'style' => 'warning' ,
                    'text'=> _('AGREE TO ADJOURN')
                ) ;
            }
            
            // This interface just has a drop down list otherBusinessList (defined at the end of this section)
            $this->interface['proposalType']= 'OtherBusiness' ;
            $availableOtherBusiness = array () ;
            $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'NONE' , _('-') ) ;
            
            /**
             * Create a collection of all cards from game decks & parties
             * Hands , unplayedProvinces, drawDeck, and discard decks not included
             */
            $allCards = new ArrayCollection() ;
            $allSenators  = new ArrayCollection() ;
            foreach ($this->game->deck as $deck)
            {
                foreach ($deck->cards as $card)
                {
                    // Do not look at cards in the unplayedProvinces, drawDeck, and discard decks
                    if (!in_array($deck->name , array ('unplayedProvinces' , 'drawDeck' , 'discard')))
                    {
                        if ($card->preciseType == 'Senator')
                        {
                            $allSenators->add($card) ;
                        }
                        else
                        {
                            // only add concessions if they are in the forum
                            if ($card->preciseType !== 'Concession' || $deck->name==='forum')
                            {
                                $allCards->add($card) ;
                            }
                        }
                    }
                }
            }
            foreach ($this->otherParties as $party)
            {
                foreach ($party->senators as $senator)
                {
                    $allSenators->add($senator) ;
                }
            }
            foreach ($this->yourParty->senators as $senator)
            {
                $allSenators->add($senator) ;
            }

            /**
            * adds element to the 'otherBusiness' attribute array of each cardPresenter based on criteria
            * - For Senators cardPresenter
            **/
            foreach ($allSenators as $senator)
            {

                // Go through controlled cards and add them to $allCards except :
                // - Senators, which represents the family of a Statesman and is not needed)
                // - Concessions, which are already assigned if we find them in a Senator->controlledCards
                foreach($senator->controlledCards as $subCard)
                {
                    if ($subCard->preciseType !== 'Senator' && $subCard->preciseType !== 'Concession')
                    {
                        $allCards->add($subCard) ;
                    }
                }
                $senatorModel = $game->getFilteredCards(array('senatorID' => $senator->getAttribute('senatorID')))->first();

                // The full contextual name is not readily available normally. Let's add it to the senator card's json
                $senator->addAttribute('fullName' , $this->game->displayContextualName($senatorModel->getFullName()));
                
                // Concession holder , Land bill sponsor & co-sponsor
                if ($senatorModel->checkCriteria('alignedInRome'))
                {
                    $senator->addAttribute('OtherBusiness' , 'concession' , TRUE);
                    $senator->addAttribute('OtherBusiness' , 'landBill' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'landBill' , _('Sponsor Land bills')) ;
                }
                
                // Possible commander
                if ($senatorModel->checkCriteria('possibleCommanders'))
                {
                    $senator->addAttribute('OtherBusiness' , 'commander' , TRUE);
                    $senator->addAttribute('MIL' , $senatorModel->getMIL());
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'commander' , _('Send Forces') ) ;
                }
                
                // Proconsul : can be recalled, can be reinforced
                if ($senatorModel->checkCriteria('isProconsul'))
                {
                    $senator->addAttribute('OtherBusiness' , 'commanderRecall' , TRUE);
                    $senator->addAttribute('OtherBusiness' , 'reinforcement' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'commanderRecall' , _('Recall ProConsul') ) ;
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'reinforcement' , _('Reinforce ProConsul') ) ;
                }

                // TO DO : 'senatorsForPontifex' , 'senatorsForPontifexRecall' , 'senatorsForPriest' , 'senatorsForPriestRecall' , 'senatorsForConsulForLife'
            }
            /**
            * adds element to the 'otherBusiness' attribute array of each cardPresenter based on criteria
            * - For normal cards cardPresenter
            **/
            foreach ($allCards as $card)
            {
                /* @var $cardModel \Entities\Card */
                $cardModel = $game->getFilteredCards(array('cardId' => $card->id))->first();
                // Add the deck as an attribute to all cards, so it can be used and displayed (for example in a drop down to show where the card is)
                $card->addAttribute('deck' , $card->location['name']);
                // Only add the concession if it's not flipped
                /* @var $cardModel \Entities\Concession */
                if ($card->preciseType==='Concession' && $cardModel->getFlipped())
                {
                    $card->addAttribute('OtherBusiness' , 'concession' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'concession' , _('Assign concessions') ) ;
                }
                elseif ($card->preciseType==='Conflict')
                {
                    $card->addAttribute('OtherBusiness' , 'commander' , TRUE);
                }
                elseif ($card->preciseType==='Province')
                {
                    $card->addAttribute('OtherBusiness' , 'garrison' , TRUE);
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'garrison' , _('Send garrions in Provinces') ) ;
                }
            }
	    
	    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'recruit' , _('Recruit Forces') ) ;
	    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'disband' , _('Disband Forces') ) ;
	    
	    // Put all non-Senator & non-Card data into a data-json json array in the otherBusinessWrapper div
            
            /**
             * Fleets
             * - addAttribute to this Presenter's json array 'fleet' ['canBeRecruited'] and ['canBeDisbanded']
             */
            $this->addAttribute('fleets' , $game->getFleetStatus()) ;

            /**
             * Legions
             */
            $this->addAttribute('legions' , $game->getLegionsStatus()) ;

	    /**
	     * Land Bills
	     */
	    foreach($game->getLandBillsTable() as $level=>$details) 
	    {
                // A law can be repealed
                if ((int)$details['inPlay']>0)
                {
                    $this->addAttribute('landBill' , array('sign'=>'-' , 'level'=>$level , 'description' => sprintf(_('Repeal Level %1$d law') , $level)) , TRUE) ;
                }
                // A law can be added
                if ((int)$details['inPlay']<=(3-(int)$level) )
                {
                    $this->addAttribute('landBill' , array('sign'=>'+' , 'level'=>$level , 'description' => sprintf(_('Pass Level %1$d law') , $level)) ,TRUE) ;
                }
            }

	    /**
            * Finally set the main drop-down of the interface to show a list of otherBusiness that are available. This is determined by having at least one card checked TRUE in the loop above
	    **/
	    $this->interface['otherBusinessList']= array
            (
                'description' => _('Proposal type') ,
                'list' => array (
                    'type' => 'select' ,
                    'class' => 'otherBusinessList',
                    'items' => $availableOtherBusiness
                )
            ) ;
        }
    }

    /**
     * Content when :
     * - A proposal is underway 
     * - The current step is "decision"
     * @param \Entities\Game $game
     * @param \Entities\Proposal $proposal
     * @param int $user_id
     */
    public function setDecisionContent($game, $proposal , $user_id) 
    {
        /**
         * Consuls proposal : The consuls now need to decide who will be Rome Consul & Field Consul
         */
        $this->interface['name'] = 'senateDecision';
        if ($proposal->getType()=='Consuls')
        {
            $this->header['list'][] = _('The parties now need to decide who will be Rome Consul & Field Consul') ;
            $this->interface['choices'] = [] ;
            /* @var $senators[] \Entities\Senator  */
            $senator = [] ;
            /* @var $parties[] \Entities\Party  */
            $party = [] ;
            
            for ($i=1 ; $i<=2 ; $i++)
            {
                $senator[$i] = $game->getFilteredCards(array('cardId'=>$proposal->getContent()[($i==1 ? 'First Senator' : 'Second Senator' )]))->first() ;
                $party[$i] = $senator[$i]->getLocation()['value'] ;
            }

            /**
             * Not part of this choice : waiting message
             */
            if (($party[1]->getUser_id()!=$user_id) && ($party[2]->getUser_id()!=$user_id))
            {
                $this->interface['description'] = _('Waiting for consuls to decide') ;
                return TRUE ;
            }

            /**
             * Part of this choice
             */
            $choiceMade = TRUE ;
            for ($i=1 ; $i<=2 ; $i++)
            {
                /**
                 * The choice was already made
                 */
                if ( ($party[$i]->getUser_id()==$user_id) && ($senator[$i]->getCardId() == $proposal->getDecision()[($i==1 ? 'First Senator' : 'Second Senator' )]) )
                {
                    $this->header['list'][] = _('You have already decided for '.$senator[$i]->getName()) ;
                }
                /**
                 * Has a choice to make
                 */
                elseif ( ($party[$i]->getUser_id()==$user_id) && ($proposal->getDecision()[($i==1 ? 'First Senator' : 'Second Senator' )]==NULL) )
               {
                    $choiceMade = FALSE ;
                    $this->header['list'][] = _('You must decide for '.$senator[$i]->getName()) ;
                    $this->interface['description'] = _('Choose role for the Senator') ;
                    $this->interface['choices'][] = array(
                        'description' => $senator[$i]->getName() ,
                        'action' => array (
                            'type' => 'select' ,
                            'class' => $senator[$i]->getCardId() ,
                            'items' => array (
                                0 => array ('description' => 'Rome Consul') ,
                                1 => array ('description' => 'Field Consul')
                            )
                        )
                    ) ;
                }
            }
            
            /**
             * You have made your choice and are waiting for the other consul
             */
            if($choiceMade)
            {
                $this->interface['description'] = _('Waiting for the other party to decide') ;
                return TRUE ;
            }
            else
            {
                $this->interface['senateDecision'] = array (
                    'type' => 'button' ,
                    'verb' => 'senateDecide' ,
                    'style'=> 'danger' ,
                    'text' => _('DONE')
                ) ;
            }

        }
        /**
         * Prosecutions - Prosecutor must agree to prosecute the prosecution on the prosecutee
         */
        if ($proposal->getType()=='Prosecutions')
        {
            $prosecutor = $game->getFilteredCards(array('senatorID'=>$proposal->getContent()['Prosecutor']))->first() ;
            if ($user_id == $prosecutor->getLocation()['value']->getUser_id())
            {
                $this->header['list'][] = _('You must agree to prosecute for the prosecution to proceed') ;
                $this->interface['description'] = 'Your decision :' ;
                $this->interface['choices'][] = array(
                    'description' => $prosecutor->getName() ,
                    'action' => array (
                        'type' => 'select' ,
                        'class'=> 'ProsecutorAgrees',
                        'items' => array (
                            0 => array ('description' => 'Agree') ,
                            1 => array ('description' => 'Disagree')
                        )
                    )
                ) ;
                $this->interface['senateDecision'] = array (
                    'type' => 'button' ,
                    'verb' => 'senateDecide' ,
                    'style'=> 'danger' ,
                    'text' => _('DONE')
                ) ;
            }
            else
            {
                $this->header['list'][] = _('The prosecutor must agree first.') ;
                $this->interface['description'] = _('Waiting for prosecutor to agree') ;
                return TRUE ;
            }
        }
        
        /**
         * commander - Must agree to go if forces sent are below minimum forces
         */
        if ($proposal->getType()=='commander')
        {
            $this->interface['description'] = _('Commanders without adequate forces') ;
            foreach ($proposal->getContent() as $item)
            {
                $commander = $game->getFilteredCards(array('SenatorID'=>$item['commander']))->first() ;
                $conflict = $game->getFilteredCards(array('cardId'=>$item['conflict']))->first() ;
                // The commander is in this user's party
                if ($commander->getLocation()['type']=='party' && $commander->getLocation()['value']->getUser_id() == $user_id)
                {
                    /** @todo check if commander has a choice */
                    $this->interface['choices'][] = array (
                        'description' => $commander->getName() ,
                        'action' => array (
                            'type'  => 'toggle' ,
                            'name' => $commander->getSenatorID() ,
                            'class' => 'toggleCommanderDecision' ,
                            'items' => array(
                                array('value' => 'FOR'     , 'description' =>_('ACCEPT')) ,
                                array('value' => 'AGAINST' , 'description' =>_('REFUSE'))
                            )
                        )
                    ) ;
                }
            }
            if (isset($this->interface['choices']))
            {
                $this->interface['senateDecision'] = array (
                    'type' => 'button' ,
                    'verb' => 'senateDecide' ,
                    'style'=> 'danger' ,
                    'text' => _('DONE')
                ) ;
            }
            else
            {
                $this->header['list'][] = _('Waiting for commanders without adequate forces to decide if they chiken out.') ;
            }
        }
        
        /**
         * Unanimous defeat
         * The HRAO was unanimously defeated and must decide to either step down or lose 1 INF
         */
        if ($proposal->getType()=='UnanimousDefeat')
        {
            $this->interface['description'] = _('The HRAO must decide how to handle unanimous defeat') ;
            if ((int)$game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $user_id)
            {
                $this->interface['description'] = _('HRAO unanimous defeat') ;
                $this->interface['choices'][] = array (
                    'description' => _('Decision') ,
                    'action' => array (
                        'type'  => 'toggle' ,
                        'name' => 'unanimousDefeatName' ,
                        'class' => 'toggleUnanimousDefeatDecision' ,
                        'default' => 'NO' ,
                        'items' => array(
                            array('value' => 'YES' , 'description' =>_('STEP DOWN')) ,
                            array('value' => 'NO'  , 'description' =>_('LOSE 1 INFLUENCE'))
                        )
                    )
                ) ;
                $this->interface['senateDecision'] = array (
                    'type' => 'button' ,
                    'verb' => 'senateDecide' ,
                    'style'=> 'danger' ,
                    'text' => _('DONE')
                ) ;

            }
            else
            {
                $this->header['list'][] = _('Waiting for the HRAO to decide.') ;
            }
        }
    }
    
    /**
     * Returns an array of how the current user can make a proposal : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , FREE TRIBUNE FROM X , TRIBUNE CARD
     * @param \Entities\Game $game
     * @return array {'type' , 'code' , 'description'}
     */
    public function getProposalHow($game)
    {
    // President
    // Prosecutions : The Censor can propose
    // Dictator appointment : Only consuls can appoint
    // Tribune Cards
    // Free tribunes from Satesmen
        $result = array() ;
        /**
        * Censor during prosecutions
        * This is exculsive (will be the only result returned)
        **/
        if ($game->getSubPhase() == 'Prosecutions')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'isCensor') ;
            if (count($searchResult)==1 && $searchResult->first()->getLocation()['value']->getUser_id() == $this->user_id)
            {
                return array(array ('type' => 'office' , 'code' => 'CENSOR' , 'description' => _('Prosecutions by the Censor')));
            }
            else
            {
                return array() ;
            }
        }
        /**
        * Consuls appointing a Dictator
        * This is exculsive (will be the only result returned)
        **/
        /*
        if ($game->getSubPhase() == 'Dictator')
        {
            $user_idThatCanAppoint=[] ;
            
            foreach($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'canAppointDictator') as $senator)
            {
                $user_idThatCanAppoint[] = $senator->getLocation()['value']->getUser_id() ;
            }
            if (in_array($this->user_id, $user_idThatCanAppoint))
            {
                return array(array ('type' => 'office' , 'code' => 'DICTATOR APPOINTMENT' , 'description' => _('Dictator appointment by consuls')));
            }
            else
            {
                return array() ;
            }
        }
         */
        /**
        * Other means of proposals : President, tribune card, free tribune
        * This is not exclusive (several results can be returned)
        **/
        if ($game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $this->user_id)
        {
            $result[] = array ('type' => 'office' , 'code' => 'PRESIDENT' , 'description' => _('President')) ;
        }
        $result = array_merge($result, $this->getFreeTribunes($game->getParty($this->user_id))) ;
        $result = array_merge($result, $this->getCardTribunes($game->getParty($this->user_id))) ;
        return $result ;
    }

    /**
     * Returns an array of parties in order of play
     * @param \Entities\Game $game
     * @return array {'user_id' , 'description'}
     */
    public function getVotingOrder($game)
    {
        $result = array() ;
        foreach ($game->getOrderOfPlay() as $party)
        {
            $result[] = array('user_id' => $party->getUser_id() , 'description' => $party->getFullName());
        }
        return $result ;
    }
    
    /**
    * Returns a list of free tribunes provided by Satesmen special abilities
    * @param \Entities\Party $party
    * @return array {'type' , 'code' , 'description'}
    **/
    public function getFreeTribunes($party)
    {
        $result = array() ;
        foreach ($party->getSenators()->getCards() as $senator)
        {
            if ($senator->getFreeTribune() == 1)
            {
                $result[] = array ('type' => 'statesman' , 'code' => $senator->getSenatorID() , 'value' =>$senator->getCardId() , 'description' => _('Free tribune from ').$senator->getName()) ;
            }
        }
        return $result ;
    }
    
    /**
    * Returns a list of tribune cards for this party
    * @param \Entities\Party $party
    * @return array {'type' , 'code' , 'description'}
    **/
    public function getCardTribunes($party)
    {
        $result = array() ;
        foreach ($party->getHand()->getCards() as $card)
        {
            if ($card->getName()=='TRIBUNE')
            {
                $result[] = array ('type' => 'tribune' , 'code' => $card->getCardId() , 'value' =>$card->getCardId() , 'description' => 'Tribune card') ;
            }
        }
        return $result ;
    }
    
    /**
     * Returns a list of Senator candidates for a specific proposal, $secondary is used if two different lists are needed for the same proposal (e.g. Prosecutions : List of accused, list of prosecutors)
     * @param \Entities\Game $game
     * @param bool $secondary
     * @return array An array of candidates with values in the format array('description' , 'senatorID')
     */
    public function getListOfCandidates($game , $secondary=FALSE , $minorOnly=FALSE)
    {
        $result = array() ;
        if ($game->getSubPhase() == 'Consuls')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleConsul') as $senator)
            {
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'value' => $senator->getSenatorID() ,
                    'senatorID' => $senator->getSenatorID()
                );
            }
        }
        elseif ($game->getSubPhase() == 'Censor')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleCensor') as $senator)
            {
                // TO DO : Check if the senator was not previously rejected in a proposal
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'value' => $senator->getSenatorID() ,
                    'senatorID' => $senator->getSenatorID()
                ) ;
            }
            // TO DO : Check if $result is empty, in which case, return all non-rejected aligned Senator in Rome
        }
        /**
         * Prosecutions : list of potential accused
         */
        elseif ($game->getSubPhase() == 'Prosecutions' && !$secondary)
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'alignedInRome') as $senator)
            {
                $possibleProsecutions = $this->getPossibleCorruptions($senator) ;
                if (count($possibleProsecutions)>0)
                {
                    foreach ($possibleProsecutions as $possibleProsecution) 
                    {
                        /**
                         * If minorOnly is TRUE, add the prosecution only if its type is minor
                         */
                        if ((!$minorOnly) || ($minorOnly && $possibleProsecution['prosecutionType']=='Minor'))
                        {
                            $result[] = array (
                                'description' => $this->game->displayContextualName($possibleProsecution['description']) ,
                                'value' => json_encode(array('Type'=>$possibleProsecution['prosecutionType'] , 'senatorID'=>$senator->getSenatorID() , 'cardId'=>$possibleProsecution['prosecutionReason'])) 
                            ) ;
                        }
                    }
                }
            }
        }
        /**
         * Prosecutions : list of potential prosecutors 
         */
        elseif ($game->getSubPhase() == 'Prosecutions' && $secondary)
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'alignedInRome') as $senator)
            {
                if ($senator->getOffice()!='Censor')
                {
                    $result[] = array (
                        'description' => $this->game->displayContextualName($senator->getFullName()) ,
                        'value' => $senator->getSenatorID() ,
                        'senatorID' => $senator->getSenatorID()
                    ) ;
                }
            }
        }
        elseif ($game->getSubPhase() == 'Governors')
        {
            // Add a "-" option linked to a NULL cardID, so no Senator is selected by default on a new drop down
            $result[] = array (
                'description' => _('-') ,
                'value' => NULL ,
                'senatorID' => NULL
            ) ;
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleGovernor') as $senator)
            {
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'value' => $senator->getSenatorID() ,
                    'senatorID' => $senator->getSenatorID()
                ) ;
            }
        }
        return $result ;
    }
    
    /**
    * List of available Province Cards for Governors proposals
    * @param \Entities\Game $game
    **/
    public function getListOfAvailableCards($game)
    {
        $result = array() ;
        if ($game->getSubPhase() == 'Governors')
        {
            // Add a "-" option linked to a NULL cardID, so no Province is selected by default on a new drop down
            $result[] = array (
                'description' => _('-') ,
                'recall' => FALSE ,
                'cardID' => NULL
            ) ;
            $result = array_merge($result , $game->getListOfAvailableProvinces()) ;
        }
        return $result ;
    }
    
    /**
    * Taken out of setContent (otherBusiness section) for readability sake
    * This function adds an available other business to the current list if it wasn't there already
     * @param array $current The current list of other business
     * @param string $value The key to insert in the list
     * @param string $description The description for that key 
     * @return array An updated list
     */
    public function addAvailableOtherBusiness($current , $value , $description)
    {
	$result = $current ;
        if (!in_array(array('description' => $description ,'value' => $value) , $current))
        {
	    $result[] = array (
		'description' => $description ,
		'value' => $value
	    ) ;
	}
	return $result ;
    }
    
    /**
     * Adds a json value {"name" : "value"} to this data_json
     * @param string $name The value's name (key)
     * @param mixed $value The value itself
     * @param boolean $mergeArray whthere or not to consider $name to be an array in which $value must be inserted
     */
    public function addAttribute($name , $value , $mergeArray = FALSE)
    {
        $json = json_decode($this->data_json , TRUE) ;
        // Merging  $value into the array $json[$name]
        if ($mergeArray)
        {
            if (!isset ($json[$name]))
            {
                $json[$name] = array() ;
            }
            $json[$name][] = $value ;
        }
        else
        {
            $json[$name] = $value ;
        }
        $this->data_json = json_encode($json) ;
    }
    
    /**
    * returns an array of possible prosecutions for this Senator:
    * 'prosecutionType' => 'Major' | 'Minor' , 'description'
    * @param \Entities\Senator $game
    **/
    public function getPossibleCorruptions($senator)
    {
        $result = array() ;
        if ($senator->getOffice() == 'Censor')
        {
            return array() ;
        }
        if ($senator->getMajor())
        {
            $result[] = array (
                'prosecutionType' => 'Major' ,
                'description' => sprintf(_('Major prosecution of %1$s for holding an office') , $senator->getFullName())
            ) ;
        }
        if ($senator->getCorrupt())
        {
            $result[] = array (
                'prosecutionType' => 'Minor' ,
                'description' => sprintf(_('Minor prosecution of %1$s for taking provincial spoils') , $senator->getFullName())
            ) ;
        }
        if ($senator->hasControlledCards())
        {
            foreach($senator->getCardsControlled()->getCards() as $card)
            {
                if ($card->getCorrupt())
                {
                    $result[] = array (
                        'prosecutionType' => 'Minor' ,
                        'prosecutionReason' => $card->getCardId() ,
                        'description' => sprintf(_('Minor prosecution of %1$s for profiting from %2$s') , $senator->getFullName() , $card->getName())
                    ) ;
                }
            }
        }
        return $result ;
    }
    
    /**
     * All Senators in Rome with their names and votes : ORA , Knights, Treasury (so they can spend some) , INF (needed during prosecutions & Consul for life)
     * @param \Entities\Game $game
     * @param \Entities\Proposal $currentProposal
     * @param int $user_id
     */
    public function getSenatorVoteList($game , $currentProposal , $user_id)
    {
        $result=array() ;
        /* @var $senator \Entities\Senator */
        foreach ($game->getParty($user_id)->getSenators()->getCards() as $senator)
        {
            $currentSenator = array() ;
            $currentSenator['name'] = $senator->getName() ;
            // Is in Rome : can vote
            if ($senator->checkCriteria('alignedInRome'))
            {
                // TO DO : vote calculation belongs in the proposal
                $oratory = $senator->getORA() ;
                $knights = $senator->getKnights() ;
                $currentSenator['votes'] = $oratory + $knights ;
                // Tooltip
                $knightsTooltip = ($knights==0 ? '' : sprintf(_(' and %1$d knights') , $knights)) ;
                $currentSenator['attributes'] = array (
                   'data-toggle' => 'popover' ,
                   'data-content' => sprintf(_('%1$d votes from %2$s Oratory%3$s.') , $currentSenator['votes'] , $oratory , $knightsTooltip ) ,
                   'data-trigger' => 'hover' ,
                   'data-placement' => 'bottom'
                ) ;
                // TO DO  : Add INF for Prosecutions & Consul for life
                // Dropdown for spedning talents
                $treasury = $senator->getTreasury() ;
                if ($treasury>0)
                {
                    $items = array() ;
                    for ($i=0 ; $i<=$treasury ; $i++)
                    {
                        $items[] = array (
                            'value' => $i ,
                            'description' => $i." T."
                        ) ;
                    }
                    $currentSenator['talents']= array (
                        'type' => 'select' ,
                        'class' => 'senatorVoteTalents_'.$senator->getSenatorID() ,
                        'items' => $items
                    ) ;
                }
                else
                {
                    $currentSenator['talents'] = 0 ;
                }
                // Toggle for split vote (when a senator votes differently from the party)
                $currentSenator['splitVote'] = array (
                    'type'  => 'toggle' ,
                    'name' => $senator->getSenatorID() ,
                    'class' => 'toggleSenatorVote' ,
                    'items' => array(
                        array('value' => 'FOR'     , 'description' =>_('FOR')) ,
                        array('value' => 'AGAINST' , 'description' =>_('AGAINST')) ,
                        array('value' => 'ABSTAIN' , 'description' =>_('ABSTAIN'))
                    )
                ) ;
            }
            // For Senators who cannot vote
            else
            {
                $currentSenator['votes'] = 0 ;
                $currentSenator['talents'] = 0 ;
                // Tooltip
                $currentSenator['attributes'] = array (
                   'data-toggle' => 'popover' ,
                   'data-content' => _('Cannot vote.') ,
                   'data-trigger' => 'hover' ,
                   'data-placement' => 'bottom'
                ) ;
            }
            $result[] = $currentSenator ;
        }
        return $result ;
    }
    
    /**
     * 
     * @param \Presenters\type $game
     * @param \Presenters\type $currentProposal
     * @param \Presenters\type $user_id
     * @return \Presenters\booleanWhether|boolean
     * @param type $game
     * @param type $currentProposal
     * @param type $user_id
     * @return booleanWhether or not this user_id can use popular appeal
     */
    public function canUsePopularAppeal($game , $currentProposal , $user_id)
    {
        try {
            return ($user_id == $game->getFilteredCards(array('senatorID'=>$currentProposal->getContent()['Accused']))->first()->getLocation()['value']->getUser_id()) ;
        } catch (Exception $ex) {
            return FALSE ;
        }
    }
}
