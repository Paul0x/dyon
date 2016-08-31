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
 *  File: content.php
 *  Type: Generic Object
 *  =====================================================================
 * 
 */

define(DYON_HOTSITE_CONTENT_GENERIC,0);
define(DYON_HOTSITE_CONTENT_TEXT,1);
define(DYON_HOTSITE_CONTENT_IMAGE,2);
define(DYON_HOTSITE_CONTENT_BUTTON,3);
define(DYON_HOTSITE_CONTENT_SLIDE,4);
define(DYON_HOTSITE_CONTENT_MENU,5);
define(DYON_HOTSITE_CONTENT_MENU_ITEM,6);
class content {
    /* Database Connection */

    private $conn;

    /* Content Basic Structure */
    private $id;
    private $type;
    private $modification_date;
    private $block_id;
    
    public function __construct() {
        $this->conn = new conn();
    }
    
    public function getId() {
        if(!is_numeric($this->id)) {
            throw new Exception("Identificador do conteúdo não informado.");
        }
        
        return $this->id;
    }
    
}
