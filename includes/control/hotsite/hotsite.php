<?php

/* * ****************************************
 *     _____                    
 *    |  __ \                   
 *    | |  | |_   _  ___  _ __  
 *    | |  | | | | |/ _ \| '_ \ 
 *    | |__| | |_| | (_) | | | |
 *    |_____/ \__, |\___/|_| |_|
 *             __/ |            
 *            |___/  
 *           
 *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ]
 *  =====================================================================
 *  File: front.php
 *  Type: Controller
 *  =====================================================================
 *  Controlador para a página inicial do WebSite público do Dyon
 *  =====================================================================
 */

require_once("system/includes/lib/Twig/Autoloader.php");
require_once("system/includes/control/evento/events.php");

class hotsiteController {

    public function loadHotsite($url) {
        $eventcontroller = new eventController();
        $event = $eventcontroller->getEventByURL($url, true);
        
        
    }

}

?>