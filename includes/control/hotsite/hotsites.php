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

require_once(config::$syspath . "includes/control/evento/events.php");

class hotsiteController {

    private $loaded_hotsite;
    private $hotsite_fields = array(
        "id" => array(
            "type" => "int",
            "format" => "ai"
        ),
        "published" => array(
            "type" => "boolean"
        ),
        "image_banner" => array(
            "type" => "text",
            "format" => "image"
        ),
        "background_color" => array(
            "type" => "hex",
            "format" => "color"
        ),
        "title_color" => array(
            "type" => "hex",
            "format" => "color"
        ),
        "date_color" => array(
            "type" => "hex",
            "format" => "color"
        ),
        "description" => array(
            "type" => "text",
            "format" => "hypertext"
        ),
        "contact_phone" => array(
            "type" => "text",
            "format" => "phone"
        ),
        "contact_email" => array(
            "type" => "text",
            "format" => "mail"
        ),
        "contact_address" => array(
            "type" => "text",
            "format" => "address"
        ),
        "button_name" => array(
            "type" => "enum",
            "values_list" => array(
                "c" => "Commprar",
                "r" => "Registrar",
                "i" => "Inscrever"
            )
        ),
        "teaser" => array(
            "type" => "text",
            "format" => "url"
        ),
        "show_sold" => array(
            "type" => "boolean"
        ),
        "show_schedule" => array(
            "type" => "boolean"
        ),
        "show_contacts" => array(
            "type" => "boolean"
        ),
        "show_gallery" => array(
            "type" => "boolean"
        ),
        "show_likes" => array(
            "type" => "boolean"
        ),
        "data_alteracao" => array(
            "type" => "datetime"
        )
    );
    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct($event_id = null) {
        $this->conn = new conn();
        if ($event_id) {
            $this->loadHotsiteByEventId($event_id);
        }
    }
    
    public function getFields() {
        if($this->loaded_hotsite) {
            return $this->loaded_hotsite;
        } else {
            throw new Exception("Hotsite não carregado.");
        }
    }

    public function loadHotsiteByEventId($event_id) {
        if (!is_numeric($event_id)) {
            throw new Exception("Identificador do evento inválido.");
        }
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser();
        } catch (Exception $ex) {
            $hotsite_public = true;
        }
        $eventcontroller = new eventController();
        if (!$eventcontroller->userHasEditPermission($user->getId(), $event_id)) {
            $hotsite_manager = false;
        }
        
        $this->conn->prepareselect("hotsite", array_keys($this->hotsite_fields), "event_id", $event_id, "same", "", "", PDO::FETCH_ASSOC);
        if(!$this->conn->executa()) {
            throw new Exception("Hotsite não encontrado.");
        }
        
        $this->loaded_hotsite = $this->conn->fetch;
        $this->loaded_hotsite['manager'] = $hotsite_manager;
        $this->loaded_hotsite['public'] = $hotsite_public;
        return $this->loaded_hotsite;
    }

    public function initManagerHotsite() {
        $usercontroller = new userController();
        try {
            $user = $usercontroller->getUser();
            $user_info = $user->getBasicInfo();
            $this->eventcontroller = new eventController();
            if ($this->eventcontroller->userHasEditPermission($user->getId(), $user_info['evento_padrao'])) {
                $edit_flag = true;
                setup::addJavascript("public/public_event_manager");
                setup::addCss("public");
            } else {
                throw new Exception("Site não disponível para edição.");
            }
            Twig_Autoloader::register();
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/public');
            $this->twig = new Twig_Environment($this->twig_loader);
            echo $this->twig->render("evento/public_event.twig", Array("event" => $event, "edit_flag" => $edit_flag, "user" => $user_info, "config" => config::$html_preload, "event_interface_flag" => true));
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

}

function init_module_hotsite() {
    $hotsitecontroller = new hotsiteController();
    $hotsitecontroller->initManagerHotsite();
}
