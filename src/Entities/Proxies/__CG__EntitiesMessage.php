<?php

namespace DoctrineProxies\__CG__\Entities;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Message extends \Entities\Message implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'id', '' . "\0" . 'Entities\\Message' . "\0" . 'game', 'text', 'parameters', 'type', 'recipients', 'from', 'time');
        }

        return array('__isInitialized__', 'id', '' . "\0" . 'Entities\\Message' . "\0" . 'game', 'text', 'parameters', 'type', 'recipients', 'from', 'time');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Message $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getGame()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGame', array());

        return parent::getGame();
    }

    /**
     * {@inheritDoc}
     */
    public function getText()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getText', array());

        return parent::getText();
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getParameters', array());

        return parent::getParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getType', array());

        return parent::getType();
    }

    /**
     * {@inheritDoc}
     */
    public function getRecipients()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRecipients', array());

        return parent::getRecipients();
    }

    /**
     * {@inheritDoc}
     */
    public function getFrom()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFrom', array());

        return parent::getFrom();
    }

    /**
     * {@inheritDoc}
     */
    public function getTime()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTime', array());

        return parent::getTime();
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setType', array($type));

        return parent::setType($type);
    }

    /**
     * {@inheritDoc}
     */
    public function setParameters($parameters)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setParameters', array($parameters));

        return parent::setParameters($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function setRecipients($recipients)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRecipients', array($recipients));

        return parent::setRecipients($recipients);
    }

    /**
     * {@inheritDoc}
     */
    public function getFlashType()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFlashType', array());

        return parent::getFlashType();
    }

    /**
     * {@inheritDoc}
     */
    public function show($user_id, $partiesNames)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'show', array($user_id, $partiesNames));

        return parent::show($user_id, $partiesNames);
    }

    /**
     * {@inheritDoc}
     */
    public function getColour()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getColour', array());

        return parent::getColour();
    }

    /**
     * {@inheritDoc}
     */
    public function isRecipient($user_id)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isRecipient', array($user_id));

        return parent::isRecipient($user_id);
    }

}
