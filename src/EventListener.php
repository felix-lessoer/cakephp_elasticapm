<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ElasticApm;

use PhilKra\Agent;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Core\Configure;
use Cake\Utility\Text;


/**
 * Description of EventListener
 *
 * @author felix
 */
class EventListener implements EventListenerInterface {

    private $agent;
    private $transaction;
    private $span;
    private $controllerSpan;

    public function __construct() {
        $config = Configure::read('ElasticApm');
        $config['env'] = ['DOCUMENT_ROOT', 'REMOTE_ADDR', 'REMOTE_USER'];

        $context = [];

        $this->agent = new Agent($config, $context);
        $this->transaction = $this->agent->startTransaction($this->buildTransactionName());
    }

    public function implementedEvents() {
        return [
            'Controller.initialize' => 'initializeController',
            "Controller.startup" => "defaultControllerEvent",
            "Controller.beforeRedirect" => "defaultControllerEvent",
            "Controller.beforeRender" => "beforeFilter",
            "Controller.shutdown" => "shutdown",
            "View.beforeRender" => "defaultEvent",
            "View.beforeRenderFile" => "defaultEvent",
            "View.afterRenderFile" => "defaultEvent",
            "View.afterRender" => "defaultEvent",
            "View.beforeLayout" => "defaultEvent",
            "View.afterLayout" => "defaultEvent",
            "Model.initialize" => "defaultModelEvent",
            "Model.beforeMarshal" => "defaultModelEvent",
            "Model.beforeFind" => "defaultModelEvent",
            "Model.buildValidator" => "defaultModelEvent",
            "Model.buildRules" => "defaultModelEvent",
            "Model.beforeRules" => "defaultModelEvent",
            "Model.afterRules" => "defaultModelEvent",
            "Model.beforeSave" => "defaultModelEvent",
            "Model.afterSave" => "defaultModelEvent",
            "Model.afterSaveCommit" => "defaultModelEvent",
            "Model.beforeDelete" => "defaultModelEvent",
            "Model.afterDelete" => "defaultModelEvent",
            "Model.afterDeleteCommit" => "defaultModelEvent"
        ];
    }

    public function initializeController(Event $event) {
        $request = $event->getSubject()->request;
        $key = $request->getParam('controller') . "." . $request->getParam('action');
        $this->controllerSpan = $this->agent->factory()->newSpan($key, $this->transaction);
        $this->controllerSpan->start();
    }

    public function defaultModelEvent(Event $event) {
        
        $this->stopLastSpan();
        $table = $event->getSubject();
        $name = explode(".", $event->getName());
        $key = $name[0]." ".$table->getAlias().".".$name[1];
        $this->span = $this->agent->factory()->newSpan($key, $this->getParent());
        $this->span->start();
    }
    
    public function defaultControllerEvent(Event $event) {
        $this->stopLastSpan();
        $request = $event->getSubject()->request;
        $name = explode(".", $event->getName());
        $key = $name[0]." ".$request->getParam('controller').".".$request->getParam("action").".".$name[1];
        $this->span = $this->agent->factory()->newSpan($key, $this->getParent());
        $this->span->start();
    }
    
    //Inject distributed tracing header
    public function beforeFilter(Event $event)
    {
        if ($this->transaction->getParentId() == null) {
            $this->transaction->setParentId(Text::uuid());
        }
        $event->setData("traceId", $this->transaction->getTraceId());
        $event->setData("spanId", $this->transaction->getParentId());
        $this->defaultControllerEvent($event);
    }

    public function defaultEvent(Event $event) {
        $this->stopLastSpan();
        $this->span = $this->agent->factory()->newSpan($event->getName(), $this->getParent());
        $this->span->start();
    }

    public function shutdown(Event $event) {
        if ($this->transaction == null) {
            debug("No transaction started");
            return;
        }
        $this->stopLastSpan();
        //Stop controller span
        $this->controllerSpan->stop();
        $this->agent->putEvent($this->controllerSpan);

        $this->agent->stopTransaction($this->transaction->getTransactionName());
    }

    private function getParent() {
        if (isset($this->controllerSpan)) {
            return $this->controllerSpan;
        }
        return $this->transaction;
    }

    private function stopLastSpan() {
        if (isset($this->span)) {
            $this->span->stop();
            $this->agent->putEvent($this->span);
        }
    }

    private function buildTransactionName() {
        $name = explode("?", $_SERVER["REQUEST_METHOD"] . " " . $_SERVER["REQUEST_URI"]);
        return $name[0];
    }

}
