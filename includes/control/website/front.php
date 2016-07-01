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
require_once("includes/control/hotsite/hotsite.php");

class websiteController {

    public function init($url) {
        $hotcontroller = new hotsiteController();
        if ($url['id']) {
            try {
                $hotcontroller->loadHotsite($url['id'], true);
            } catch (Exception $ex) {
                echo $ex->getMessage();
                
            }
        } else {
            
        }
    }

}

function init_module_index($url) {
    $controller = new websiteController();
    $controller->init($url);
}

?>