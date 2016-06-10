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
 *  File: render.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

class render {
    
    public function __construct() {
        
        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/hotsite/render');
        $this->twig = new Twig_Environment($this->twig_loader);
    }
    public function bodyCss($attrs) {        
        return $this->twig->render("css/body.twig", Array("attrs" => $attrs));
        
    }


}
