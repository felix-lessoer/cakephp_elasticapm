<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    use ElasticApm\EventListener;
    use Cake\Event\EventManager;
    
    EventManager::instance()->on(new EventListener());