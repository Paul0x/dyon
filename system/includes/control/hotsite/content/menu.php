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

class menu extends content {

    private $links;
    private $menu_align;
    private $hover_color;
    private $link_color;
    private $text_color;
    private $background_color;

    public function __construct() {
        parent::__construct();
    }

    public function getMenuLinks() {
        if (!is_numeric($this->id)) {
            throw new Exception("O identificador do menu não é válido.");
        }

        $search_args = array("id_parente", "tipo_parente", "tipo");
        $search_values = array($this->id, DYON_HOTSITE_CONTENT_MENU, DYON_HOTSITE_CONTENT_MENU_LINK);
        $this->conn->prepareselect("conteudo", "id", $search_args, $search_values, "same", "", "", NULL, "all");
        if (!$this->conn->executa()) {
            throw new Exception("Esse menu não possui nenhum link.");
        }

        $links_id = $this->conn->fetch;
        $this->links = array();
        foreach ($links_id as $index => $link_id) {
            $this->links[] = new menuLink($link_id[0]);
        }
    }
    
    private function load() {
        $fetched = $this->loadFromDb();
        $info_database = unserialize($fetched['info']);
        if (!is_array($info_database)) {
            throw new Exception("Informações do conteúdo não registradas.");
        }
        
        $this->setMenuAlign($info_database['menu_align']);
        $this->setColor($info_database['hover_color'], "hover_color");
        $this->setColor($info_database['link_color'], "link_color");
        $this->setColor($info_database['text_color'], "text_color");
        $this->setColor($info_database['background_color'], "background_color");
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
        $this->type = DYON_HOTSITE_CONTENT_MENU;
        $this->getMenuLinks();
        $this->load();
    }

    public function render() {
        if(is_array($this->links)) {
            foreach($this->links as $index => $link) {
                $menu_links[] = $link->render();
            }
        }
        
        $settings['id'] = $this->id;
        $settings['menu_align'] = $this->menu_align;
        $settings['hover_color'] = $this->hover_color;
        $settings['link_color'] = $this->link_color;
        $settings['text_color'] = $this->text_color;
        $settings['background_color'] = $this->background_color;


        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
        $this->twig = new Twig_Environment($this->twig_loader);
        return $this->twig->render("hotsite/content/menu.twig", Array("config" => config::$html_preload, "menu_settings" => $settings, "menu_links" => $menu_links));
    }
    
    public function setMenuAlign($menu_align) {
        if($menu_align != 0 && $menu_align != 1) {
            throw new Exception("Alinhamento do menu inválido.");
        }
    }
    
    public function setColor($color, $var) {
        $hex_check = "/^[0-9A-F]{6}$/";
        if(!preg_match($hex_check,$color)) {
            throw new Exception("Cor inválida.");
        }
        
        $this->$var = $color;
    }

}
