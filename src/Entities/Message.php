<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="messages")
 **/
class Message
{
    public static $VALID_TYPES = array ('log' , 'alert' , 'error' , 'chat');
    public static $FLASH_TYPES = array ('log' => 'info' , 'alert' => 'warning' , 'error' => 'danger' , 'chat' => 'success') ;

    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    // One Game has many messages
    /** @ManyToOne(targetEntity="Game", inversedBy="messages", cascade={"persist"}) **/
    private $game ;
    
    /** @Column(type="integer") @var int */
    protected $tick ;
    
    /** @Column(type="text") @var text */
    protected $text ;
    
    /** @Column(type="array") @var array */
    protected $parameters = array() ;
    
    /** @Column(type="string") @var string */
    protected $type ;

    /** @ManyToMany(targetEntity="Party", inversedBy="messages" , cascade={"persist"}  ) **/
    protected $recipients = NULL ;
    
    /** @OneToOne(targetEntity="Party", cascade={"persist"}) @JoinColumn(name="messageFrom_id", referencedColumnName="id" , nullable=true) **/
    protected $from = NULL ;
    
    /** @Column(type="datetime")  */
    protected $time ;
    
    /** @OneToOne(targetEntity="Trace", cascade={"persist" , "remove"}) @JoinColumn(name="traceId", referencedColumnName="traceId" , onDelete="CASCADE") **/
    protected $trace = NULL ;
    
    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getText() { return $this->text; }
    public function getParameters() { return $this->parameters; }
    public function getType() { return $this->type; }
    public function getRecipients() { return $this->recipients; }
    public function getFrom() { return $this->from; }
    public function getTime() { return $this->time ; }
    /** @return \Entities\Trace */
    public function getTrace() { return $this->trace ; }

    /**
     * @param Entity\Game $game The Entity\Game to which this message belongs to
     * @param string $text A string with a sprintf format, including the order of parameters (%1$s , %2$d , etc) to handle possible mixing because of i18n
     * @param string $type message|alert|error|chat
     * @param mixed|NULL $parameters An array of values to be used in the text or NULL if the text has no parameters. If $parameters is not an array and not NULL, it's cast as array($parameters)
     * @param Entity\Party array|NULL $recipients An array of all the recipients parties or NULL if everyone
     * @param Entity\Party array|NULL $from Entity\Party of the sender or NULL if this is not a chat message
     * @throws Exception
     */
    public function __construct($game , $text , $type='log' , $parameters=NULL , $recipients=NULL , $from=NULL ) {
        /* Error if :
         * - Text is empty
         * - Not the same number of vsprintf arguments and elements in the parameters array
         * - Wrong type
         * - recipients has elements that are not number
         * - from is not a number
         * - from is in recipients
         * - from is set but type is not 'chat'
         * - from is not set but type is 'chat'
         * Note : There is no check on whether or not recipients & from are existing user_ids
         * TO DO : Not the same number of vsprintf arguments and elements in the parameters array
         */
        if (strlen($text)==0) {
            throw new \Exception(_('ERROR - Message test cannot be empty.'));
        }
        if (!in_array($type, self::$VALID_TYPES)) {
            throw new \Exception(_('ERROR - Invalid message type.'));
        }
        if ($from!=NULL && get_class($from)!= \Entities\Party) {
            throw new \Exception(_('ERROR - Invalid Sender.'));
        }
        if ($from!=NULL && in_array($from , $recipients)) {
            throw new \Exception(_('ERROR - Sender present in recipients list.'));
        }
        if ($type!='chat' && $from!=NULL) {
            throw new \Exception(_('ERROR - Sender for a non-chat message.'));
        }
        if ($type=='chat' && $from==NULL) {
            throw new \Exception(_('ERROR - Chat without a sender.'));
        }
        if ($recipients!=NULL) {
            $this->recipients = new ArrayCollection();
            foreach($recipients as $recipient) {
                if (get_class($recipient)!= 'Entities\\Party' ) {
                    throw new \Exception(_('ERROR - Invalid recipient.'));
                } else {
                    $this->recipients->add($recipient) ;
                    $recipient->addMessage($this) ;
                }
            }
        } else {
            $this->recipients = NULL ;
        }
        $this->game = $game ;
        $this->tick = $game->getTick() ;
        $this->text = $text ;
        if (is_array($parameters)) {
            $this->parameters = $parameters ;
        } else {
            $this->parameters = array ($parameters) ;
        }
        $this->type = $type ;
        $this->from = $from ;
        $this->time = new \DateTime('NOW') ;
    }

    public function setType($type) {
        if (!in_array($type, self::$VALID_TYPES)) {
            throw new \Exception(_('ERROR - Invalid message type.'));
        } else {
            $this->type = $type ;
        }
    }
    
    public function setParameters($parameters) {
        if (is_array($parameters)) {
            $this->parameters = $parameters ;
        } else {
            $this->parameters = array ($parameters) ;
        }
    }
    
    public function setRecipients($recipients) {
        if ($recipients!=NULL) {
            $this->recipients = new ArrayCollection();
            foreach($recipients as $recipient) {
                if (get_class($recipient)!= 'Entities\\Party' ) {
                    throw new \Exception(_('ERROR - Invalid recipient.'));
                } else {
                    $this->recipients->add($recipient) ;
                    $recipient->addMessage($this) ;
                }
            }
        } else {
            $this->recipients = NULL ;
        }
    }
    
    /*
     * A simple mapping from $this->type to a Flash Type
     */
    public function getFlashType() {
        return (in_array($this->type , self::$VALID_TYPES) ? self::$FLASH_TYPES[$this->type] : self::$FLASH_TYPES['error']) ;
    }
    
    /**
     * Displays the message, taking $user_id into account<br>
     * NOTE : The following special tags will be replaced :<br>
     * - [[$id]], will be replaced by the party & name of the player with id $id, or "You" if the $id is the same as $user_id<br>
     * - A tag in the format {do,does} only works if a previous [[]] tag exists, in which case "do" will be used if $id = $user_id and "does" otherwise<br>
     * 
     * How it works : It goes through each user_id in the partiesNames array, if it finds [[$id]], it replaces it with<br>
     * the name of the party or 'you', based on the $user_id parameter (which tells us who is reading the message)<br>
     * it then searches for an optional plural (like {get,gets}) to change <br>
     * @param integer $user_id
     * @param array $partiesNames ('id' => 'Full Name')
     * @return string Message formatted for output
     */
    public function show($user_id , $partiesNames) {
        // First, replace parameters by their value in the text
        $formattedMessage = vsprintf($this->text, $this->parameters) ;
        foreach($partiesNames as $party_id => $party_name) {
            if (strpos($formattedMessage, '[['.$party_id.']]') !==FALSE) {
                $name = ( ($party_id==$user_id) ? 'you' : $party_name ) ;
                $formattedMessage = str_replace('[['.$party_id.']]' , $name , $formattedMessage);
                // Replace the optional {receive,receives} values
                while (strpos($formattedMessage , '{')!==FALSE)
                {
                    $plural_pos = strpos($formattedMessage , '{') ;
                    $plural_pos2 = strpos($formattedMessage , '}' , $plural_pos) ;
                    if ($plural_pos!=FALSE && $plural_pos2!=FALSE) {
                        $plural = substr($formattedMessage, $plural_pos, $plural_pos2-$plural_pos+1) ;
                        $plurals = explode(',', substr(substr($plural,1),0,-1)) ;
                        $plural_text = ( ($party_id==$user_id) ? $plurals[0] : $plurals[1]) ;
                        $formattedMessage = str_replace($plural , $plural_text , $formattedMessage);
                    }
                } 
            }
        }
        
        /*
        if ($this->type=='chat') {
            $recipientsList = '' ;
            if ($this->recipients===NULL) {
                $recipientsList = 'everyone';
            } else {
                foreach ($this->recipients as $user_id) {
                    $recipientsList.=$playerNames[$user_id].' , ';
                }
                $recipientsList=substr($recipientsList, 0 , -3);
            }
            $formattedMessage = $playerNames[$this->from].' says to '.$recipientsList.' : '.$this->text ;
        }
        */
        return ucfirst( $formattedMessage );
    }
    
    /**
     * An array with all the information needed to properly display the message in the log 
     * @param type $user_id
     * @param type $partiesNames
     * @return type
     */
    public function getLogVersion($user_id , $partiesNames) 
    {
        return array (
            'time'              => $this->time ,
            'colour'            => $this->getColour() ,
            'text'              => $this->show($user_id, $partiesNames) ,
            'traceDescription'  => $this->getTraceDescription() ,
            'traceOperation'    => $this->getTraceOperation() ,
            'proposalId'        => $this->getProposalId() ,
            'proposalFinished'  => $this->isProposalFinished()
        ) ;
    }
            
    public function getColour() {
        switch($this->type) {
            case 'chat'     : $result='seagreen' ;  break ;
            case 'alert'    : $result='orange' ;    break ;
            case 'error'    : $result='red' ;       break ;
            default         : $result='indigo' ;
        }
        return $result ;
    }
    
    public function isRecipient($user_id) {
        $recipients = $this->getRecipients() ;
        if ($recipients===NULL) {return TRUE;}
        if (count($recipients)==0) {return TRUE;}
        foreach($recipients as $recipient) {
            if ($recipient->getUser_id()==$user_id) {
                return TRUE ;
            }
        }
        return FALSE ;
    }
    
    /**
     * ------------------------------
     *        Trace related
     * ------------------------------
     */
    
    /**
     * @param string $operation
     * @param array $parameters
     * @param array $entities
     * @throws \Exception
     */
    public function recordTrace($operation , $parameters=NULL , $entities=NULL)
    {
        try {
            /*
            // $entities is passed as an array to allow for control of the order in which the Trace->entities ArrayCollection will be created
             * cancel that : couldn't guaranty the order anyway. Saved it inside a proper array
            if ($entities)
            {
                $entitiesArrayCollection = new ArrayCollection() ;
                foreach ($entities as $entity)
                {
                    $entitiesArrayCollection->add($entity) ;
                }
            }
            else
            {
                $entitiesArrayCollection = NULL ;
            }*/
            $this->trace = new \Entities\Trace($operation , $parameters , $entities) ;
        } catch (Exception $ex) {
            throw new \Exception($ex) ;
        }
    }
    
    /**
     * @return string|bool
     */
    public function getTraceDescription()
    {
        /* @var $trace \Entities\Trace */
        $trace = $this->trace ;
        return ($trace!==NULL ? $trace->describe() : FALSE);
    }
    
    /**
     * @return string|bool
     */
    public function getTraceOperation()
    {
        /* @var $trace \Entities\Trace */
        $trace = $this->trace ;
        return ($trace!==NULL ? $trace->getOperation() : FALSE);
    }
    
    /**
     * If this message's trace's operation is 'Proposal', returns the Id of the proposal
     * Otherwise returns -1
     * @return integer
     */
    public function getProposalId()
    {
        /* @var $trace \Entities\Trace */
        if (($this->trace) && ($this->trace->getOperation()=='Proposal'))
        {
            return $this->trace->getEntities()->first()->getId() ;
        }
        else
        {
            return -1 ;
        }
    }

    /**
     * If this message's trace's operation is 'Proposal', returns TRUE if the proposal is still underway
     * @return integer
     */
    public function isProposalFinished()
    {
        if (($this->trace) && ($this->trace->getOperation()=='Proposal'))
        {
            return ($this->trace->getEntities()->first()->isFinished() ) ;
        }
        else
        {
            return FALSE ;
        }
    }
}
