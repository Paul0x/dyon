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
 *  File: hotsite.php
 *  Type: Generic Object
 *  =====================================================================
 * 
 */

class hotsite {
    
    /* Hotsite Basic Structure */
    private $event_id;
    private $pages;
    private $event;
    
    /* Hotsite CSS Structure */
    private $text_color;
    private $text_font;
    private $title_color;
    private $title_font;
    private $background_color;
    private $background_image;
    
    /* Hotsite Pages Structure */
    private $gallery_status;
    private $contact_status;
    private $schedule_status;
    private $faq_status;
    private $blog_status;
    
    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct(conn $conn, $event_id = null) {
        $this->conn = $conn;
        
        if(!is_null($event_id)) {
            $this->loadHotsite($event_id);
        }
    }
    
    public function loadHotsite($event_id) {
        echo $event_id;
        
    }

}
