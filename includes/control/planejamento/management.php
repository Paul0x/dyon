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
 *  File: management.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/control/usuario/users.php");
require_once("includes/lib/Twig/Autoloader.php");
require_once("includes/control/board/thread.php");
require_once("includes/control/comentario/comments.php");

class managementController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }


    public function init($url) {
            Twig_Autoloader::register();
            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/manager');
            $this->twig = new Twig_Environment($this->twig_loader);
            echo $this->twig->render("planejamento/main_management.twig", array("config" => config::$html_preload, "user" => $user->getBasicInfo()));
    }
    

}

function init_module_management($url) {
    $managementcontroller = new managementController();
    $managementcontroller->init($url);
}
