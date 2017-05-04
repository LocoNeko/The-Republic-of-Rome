<?php
namespace Presenters ;
use Doctrine\Common\Collections\ArrayCollection;
// TO DO : Beginning of Senate phase - Initialise free tribunes of all senators
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
        /* @var $currentProposal \Entities\Proposal  */
        $currentProposal = $game->getProposals()->last() ;
        /**
         * There is a proposal underway
         */
        if ($currentProposal!==FALSE)
        {
            $this->header['description'] .= _(' - Proposal underway');
            $this->header['list'] = array (
                $this->game->displayContextualName($game->getProposals()->last()->getDescription() , $user_id)
            );
            /**
             * Currently voting
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
                }
                /**
                 * Voting interface (I could check for 'state' == 'voting', but why should I ? exception has been caught before)
                 */
                else
                {
                   // TO DO
                    /* 
                     * - A list of Senators reflecting the YES/NO of the general switch, but that can be overriden. Each Senator also has a treasury drop down
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
                    $vetoes = [] ;
                    $vetoes = array_merge($vetoes , $this->getFreeTribunes($game->getParty($user_id))) ;
                    $vetoes = array_merge($vetoes , $this->getCardTribunes($game->getParty($user_id))) ;
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
            else 
            {
                $this->header['list'][] = 'TO DO....';
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
            * This user has at least one way of making a proposal
            **/
            if (count($listProposalHow)>0)
            {
                // Bring up the senateMakeProposal interface
                $this->interface['name'] = 'senateMakeProposal';

                /**
                * How the Proposal is made :
                * Items consists of arrays with 'type' , 'code', and 'description'
                * Types : office, statesman, tribune
                * Code : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , {senatorID} , {cardID}
                **/
                // TO DO : rethink the codes for senators & cards. They could both be card IDs : then if the card is a Senator, it's a free tribune, otherwise it's a tribune card
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
                if ($targetCard->getId() == $card->id)
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
        * Consuls
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
                _('You must select a Senator') ,
                _('Only Senators aligned in Rome who have a major or minor corruption marker can be prosecuted') ,
            ) ;
            $this->interface['proposalType']= 'Prosecutions' ;
            $this->interface['prosecutions']= array
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
                //$senator->addAttribute('fullName' , $this->game->displayContextualName($senatorModel->getFullName()));
                $senator->addAttribute('fullName' , $this->game->displayContextualName($senatorModel->getFullName()));
                
                // Concession holder , Land bill sponsor & co-sponsor
                if ($senatorModel->checkCriteria('alignedInRome'))
                {
                    $senator->addAttribute('otherBusiness' , 'concession' , TRUE);
                    $senator->addAttribute('otherBusiness' , 'landBill' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'landBill' , _('Sponsor Land bills')) ;
                }
                
                // Possible commander
                if ($senatorModel->checkCriteria('possibleCommanders'))
                {
                    $senator->addAttribute('otherBusiness' , 'commander' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'commander' , _('Send Forces') ) ;
                }
                
                // Proconsul : can be recalled, can be reinforced
                if ($senatorModel->checkCriteria('isProconsul'))
                {
                    $senator->addAttribute('otherBusiness' , 'commanderRecall' , TRUE);
                    $senator->addAttribute('otherBusiness' , 'reinforcement' , TRUE);
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
                $cardModel = $game->getFilteredCards(array('cardID' => $card->id))->first();
                if ($card->preciseType==='Concession')
                {
                    $card->addAttribute('otherBusiness' , 'concession' , TRUE);
                    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'concession' , _('Assign concessions') ) ;
                }
                elseif ($card->preciseType==='Conflict')
                {
                    $card->addAttribute('otherBusiness' , 'commander' , TRUE);
                    $card->addAttribute('deck' , $card->location['name']);
                }
                elseif ($card->preciseType==='Province')
                {
                    $card->addAttribute('otherBusiness' , 'garrison' , TRUE);
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
            $fleets_canBeRecruited = 0 ;
            $fleets_inRome = 0 ;
            $fleetsOnCards = array() ;
            foreach($game->getFleets() as $fleet) 
            {
                $fleets_canBeRecruited += ($fleet->canBeRecruited() ? 1 : 0) ;
                $fleets_inRome += ($fleet->inRome() ? 1 : 0) ;
                // If the fleet has a cardLocation(), add 1 to the $fleetsOnCards array with this cardID as key
                $card = $fleet->getCardLocation() ;
                if ($card!==NULL)
                {
                    $cardId = $card->getId() ;
                    if (array_key_exists ( $cardId , $fleetsOnCards ))
                    {
                        $fleetsOnCards[$cardId]++ ;
                    }
                    else
                    {
                        $fleetsOnCards[$cardId] = 1 ;
                    }
                }
            }
            $this->addAttribute('fleets' , array('canBeRecruited'=>$fleets_canBeRecruited , 'inRome'=>$fleets_inRome , 'onCards' => $fleetsOnCards , 'cost' => $game->getUnitCost('Fleet'))) ;

            /**
             * Legions
             */
            $regulars_canBeRecruited = 0 ;
            $regulars_canBeDisbanded = 0 ;
            $regulars_inRome = 0 ;
            $regularsOnCards = array() ;
            $veterans = array() ;
            // Regular legions : how many in Rome, in the pool, with a commander ->addAttribute('regulars' , X)
            // Veteran legion : for each - its allegiance & if it's in Rome or with a commander (X , Location) where X is the allegiance
            foreach($game->getLegions() as $legion)
            {
                $card = $legion->getCardLocation() ;
                // regular
                if (!$legion->getVeteran())
                {
                    $regulars_canBeRecruited += ($legion->canBeRecruited() ? 1 : 0) ;
                    $regulars_canBeDisbanded += ($legion->canBeDisbanded() ? 1 : 0) ;
                    $regulars_inRome += ($legion->isRegularInRome() ? 1 : 0) ;
                    
                    if ($card!==NULL)
                    {
                        $cardId = $card->getId() ;
                        if (array_key_exists ( $cardId , $regularsOnCards ))
                        {
                            $regularsOnCards[$cardId]++ ;
                        }
                        else
                        {
                            $regularsOnCards[$cardId] = 1 ;
                        }
                    }
                }
                // veteran
                else
                {
                    $veterans[$legion->getId()] = array (
                        'name' => $legion->getName() ,
                        'loyalTo' => $legion->getLoyalToSenatorID() ,
                        'otherLocation' => $legion->getOtherLocation() ,
                        'cardLocation' => $legion->getCardLocationCardId()
                    ) ;
                }
            } 
            $this->addAttribute('legions' , 
                array (
                    'regularsCanBeRecruited'=>$regulars_canBeRecruited ,
                    'regularsCanBeDisbanded'=>$regulars_canBeDisbanded ,
                    'regularsInRome'=>$regulars_inRome ,
                    'veterans' => $veterans ,
                    'cost' => $game->getUnitCost('Legion')
                )
            ) ;

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
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array(array ('type' => 'office' , 'code' => 'CENSOR' , 'description' => _('Proscutions by the Censor')));
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
        if ($game->getSubPhase() == 'DictatorAppointment')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'canAppointDictator') ;
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array(array ('type' => 'office' , 'code' => 'DICTATOR APPOINTMENT' , 'description' => _('Dictator appointment by consuls')));
            }
            else
            {
                return array() ;
            }
        }
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
                $result[] = array ('type' => 'statesman' , 'code' => $senator->getSenatorID() , 'value' =>$senator->getId() , 'description' => _('Free tribune from ').$senator->getName()) ;
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
                $result[] = array ('type' => 'tribune' , 'code' => $card->getId() , 'value' =>$card->getId() , 'description' => 'Tribune card') ;
            }
        }
        return $result ;
    }
    
    /**
     * Returns a list of Senator candidates for a specific proposal
     * @param \Entities\Game $game
     * @return array An array of candidates with values in the format array('description' , 'senatorID')
     */
    public function getListOfCandidates($game)
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
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'priorConsul') as $senator)
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
        elseif ($game->getSubPhase() == 'Prosecutions')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'alignedInRome') as $senator)
            {
                $possibleProsecutions = $this->getPossibleCorruptions($senator) ;
                if (count($possibleProsecutions)>0)
                {
                    foreach ($possibleProsecutions as $possibleProsecution) 
                    {
                        // TO DO : check if there already was a minor prosecution (in which case, a major prosecution is impossible)
                        $result[] = array (
                            'prosecutionType' => $possibleProsecution['type'] ,
                            'description' => $possibleProsecution['description'] ,
                            'value' => $senator->getSenatorID() ,
                            'senatorID' => $senator->getSenatorID()
                        ) ;
                    }
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
            foreach ($game->getFilteredCards(array('preciseType' => 'Province' , 'isProvinceInPlay' => TRUE)) as $province)
            {
                // If the card is not in the Forum, this is a recall
                $recall = $province->getDeck()->getName() !== 'Forum' ;
                $result[] = array (
                    'description' => $province->getName().($recall ? sprintf(_(' (recall of %1$s)') , $province->getDeck()->getControlled_by()->getName()) : '') ,
                    'recall' => $recall ,
                    'cardID' => $province->getId()
                ) ;
            }
        }
        return $result ;
    }
    
    /**
    * Taken out of setContent (otherBusiness section) for readibility's sake
    * This function adds an available other busines to the current list if it wasn't there already
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
            {
                $json[$name] = array() ;
            }
             */
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
            foreach($senator->getCardsControlled() as $card)
            {
                if ($card->getCorrupt())
                {
                    $result[] = array (
                        'prosecutionType' => 'Minor' ,
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
}
