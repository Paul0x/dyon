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
define(DYON_HOTSITE_CONTENT_LINK_LIST,7);
define(DYON_HOTSITE_CONTENT_LINK_LIST_ITEM,8);
class content {
    /* Database Connection */

    private $conn;

    /* Content Basic Structure */
    private $id;
    private $type;
    private $modification_date;
    private $parent_id;
    private $parent_type;
    
    public function __construct($id = false) {
        $this->conn = new conn();
        
        if($id && is_numeric($id)) {
            $this->init($id);
        }
    }
    
    public function getId() {
        if(!is_numeric($this->id)) {
            throw new Exception("Identificador do conteúdo não informado.");
        }
        
        return $this->id;
    }
    
    public function getAvailableContentTypes() {
        $content_types = Array(
            "text" => Array("id" => DYON_HOTSITE_CONTENT_TEXT, "label" => "Texto", "icon" => "fa-pencil"),
            "image" => Array("id" => DYON_HOTSITE_CONTENT_IMAGE, "label" => "Imagem", "icon" => "fa-file-image-o"),
            "button" => Array("id" => DYON_HOTSITE_CONTENT_BUTTON, "label" => "Botão", "icon" => "fa-hand-o-up"),
            "slide" => Array("id" => DYON_HOTSITE_CONTENT_SLIDE, "label" => "Slideshow", "icon" => "fa-picture-o"),
            "link_list" => Array("id" => DYON_HOTSITE_CONTENT_LINK_LIST, "label" => "Lista de Links", "icon" => "fa-link")
        );
        
        return $content_types;
    }
    
}
