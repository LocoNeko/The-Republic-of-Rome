<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Description of Message
 *
 */
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
    /** @ManyToOne(targetEntity="Game", inversedBy="messages") **/
    private $game ;
    
    /** @Column(type="string") @var string */
    protected $text ;
    
    /** @Column(type="array") @var array */
    protected $parameters = array() ;
    
    /** @Column(type="string") @var string */
    protected $type ;

    /** @ManyToMany(targetEntity="Party", inversedBy="messages" , cascade={"persist"}  ) **/
    protected $recipients = NULL ;
    
    /** @OneToOne(targetEntity="Party") @JoinColumn(name="messageFrom_id", referencedColumnName="id" , nullable=true) **/
    protected $from ;
    
    /** @Column(type="datetime")  */
    protected $time ;
    
    function getId() {
        return $this->id;
    }

    function getGame() {
        return $this->game;
    }

    function getText() {
        return $this->text;
    }

    function getParameters() {
        return $this->parameters;
    }

    function getType() {
        return $this->type;
    }

    function getRecipients() {
        return $this->recipients;
    }

    function getFrom() {
        return $this->from;
    }

    public function getTime() {
        return $this->time ;
    }

        
    /**
     * @param Entity\Game $game The Entity\Game to which this message belongs to
     * @param string $text A string with a sprintf format, including the order of parameters (%1$s , %2$d , etc) to handle possible mixing because of i18n
     * @param string $type message|alert|error|chat
     * @param array|NULL $parameters An array of values to be used in the text or NULL if the text has no parameters
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
                if (get_class($recipient)!= \Entities\Party) {
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
        $this->text = $text ;
        $this->parameters= $parameters ;
        $this->type = $type ;
        $this->from = $from ;
        $this->time = new \DateTime('NOW') ;
    }
    
    /*
     * A simple mapping from $this->type to a Flash Type
     */
    public function getFlashType() {
        return (in_array($this->type , self::$VALID_TYPES) ? self::$FLASH_TYPES[$this->type] : self::$FLASH_TYPES['error']) ;
    }
    
    public function show() {
        $formattedMessage = $this->text ;
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
        return vsprintf($formattedMessage, $this->parameters) ;
    }
    
    public function colour() {
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
        if ($recipients !== NULL) {
            foreach($recipients as $recipient) {
                if ($recipient->getUser_id()==$user_id) {
                    return TRUE ;
                }
            }
        }
        return FALSE ;
    }
}