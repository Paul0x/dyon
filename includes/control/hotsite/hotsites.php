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
 *  File: hotsites.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

class hotsiteController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct($event_id = null) {
        $this->conn = new conn();
        if ($event_id) {
            $this->loadHotsiteByEventId($event_id);
        }
    }
    
    public function loadHotsiteByEventId($event_id) {
        
    }

}
