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
 *  File: menu.php
 *  Type: Main Menu Content Controller
 *  =====================================================================
 * 
 */

class menuLink extends content {

    private $links;

    public function __construct($id) {
        parent::__construct($id);
    }


    public function init($id) {
        if (!is_numeric($id)) {
            throw new Exception("O identificador do menu é inválido.");
        }
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        $hotsite = unserialize($_SESSION['hotsitecache']);

        if (!is_a($hotsite, "hotsite")) {
            throw new Exception("O hotsite não está carregado corretamente.");
        }

        $hotsite->checkPermission($user);
        $this->id = $id;
    }

    public function render() {
        
    }

}
