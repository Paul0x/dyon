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
 *  File: contents.php
 *  Type: Content Controller
 *  =====================================================================
 * 
 */

define(DYON_HOTSITE_CONTENT_GENERIC, 0);
define(DYON_HOTSITE_CONTENT_TEXT, 1);
define(DYON_HOTSITE_CONTENT_IMAGE, 2);
define(DYON_HOTSITE_CONTENT_BUTTON, 3);
define(DYON_HOTSITE_CONTENT_SLIDE, 4);
define(DYON_HOTSITE_CONTENT_MENU, 5);
define(DYON_HOTSITE_CONTENT_MENU_ITEM, 6);
define(DYON_HOTSITE_CONTENT_LINK_LIST, 7);
define(DYON_HOTSITE_CONTENT_LINK_LIST_ITEM, 8);

require_once("includes/control/hotsite/content/content.php");
require_once("includes/control/hotsite/content/menu.php");

class contentController {
    /* Database Connection */

    private $conn;

    public function __construct($hotsite) {
        $this->conn = new conn();
        $usercontroller = new userController();
        $this->user = $usercontroller->getUser();
        if (!is_a($hotsite, "hotsite")) {
            throw new Exception("Hotsite inválido.");
        }

        if (!$hotsite->checkPermission($this->user)) {
            throw new Exception("O usuário não tem permissão para carregar ou alterar conteúdos desse hotsite.");
        }
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

    public function getContentsByBlock($block_id, $rendered_contents = false) {
        if (!is_numeric($block_id)) {
            throw new Exception("O identificador do bloco é inválido.");
        }

        $content_fields = array("id","id_parente","data_alteracao","tipo","info","tipo_parente");
        $this->conn->prepareselect("conteudo", $content_fields, array("id_parente","tipo_parente"), array($block_id, 1), "same", "", "", NULL, "all");
        if(!$this->conn->executa()) {
            throw new Exception("Nenhum conteúdo encontrado para o bloco indicado.");
        }
        
        $contents_fetched = $this->conn->fetch;
        foreach($contents_fetched as $index => $content) {
            $content_object = $this->generateContent($content);
            if($rendered_contents) {
                $contents[] = $content_object->render();
            } else {
                $contents[] = $content_object;
            }
        }
        
        return $contents;        
    }
    
    private function generateContent($content) {
        $content_types = array(
            0 => "content",
            1 => "text",
            2 => "image",
            3 => "button",
            4 => "slideshow",
            5 => "menu",
            6 => "menu_item",
            7 => "link_list",
            8 => "link_list_item"
        );
        
        $obj = new $content_types[$content["tipo"]]();
        $obj->init($content['id']);
        
        return $obj;        
    }
}
