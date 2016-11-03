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

    private $link_type;
    private $url;
    private $target;
    private $title;

    public function __construct($id) {
        parent::__construct($id);
    }

    public function init($id) {
        if (!is_numeric($id)) {
            throw new Exception("O identificador do link é inválido.");
        }
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        $hotsite = unserialize($_SESSION['hotsitecache']);

        if (!is_a($hotsite, "hotsite")) {
            throw new Exception("O hotsite não está carregado corretamente.");
        }

        $hotsite->checkPermission($user);
        $this->id = $id;
        $this->type = DYON_HOTSITE_CONTENT_MENU_LINK;

        $this->load();
    }

    private function load() {
        $fetched = $this->loadFromDb();
        $info_database = unserialize($fetched['info']);
        if (!is_array($info_database)) {
            throw new Exception("Informações do conteúdo não registradas.");
        }

        $this->setLinkType($info_database['link_type']);
        $this->setUrl($info_database['url']);
        $this->setTarget($info_database['target']);
        $this->setTitle($info_database['title']);
    }

    public function setLinkType($link_type) {
        if (!is_numeric($link_type) || $link_type > 4 || $link_type < 0) {
            throw new Exception("Tipo de link inválido.");
        }

        $this->link_type = $link_type;
    }

    public function setUrl($url) {
        $pattern = '/^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)$/';
        if (!preg_match($pattern, $url)) {
            throw new Exception("URL inválida.");
        }

        $this->url = $url;
    }

    public function setTarget($target) {
        if ($target != "new" && $target != "blank" && $target != "self") {
            throw new Exception("Target inválido.");
        }

        $this->target = $target;
    }

    public function setTitle($title) {
        $title = trim($title);
        $pattern = "/^[a-zA-Z1-9ç\s]*$/";
        if ($title == "" || !preg_match($pattern, $title)) {
            throw new Exception("Nome do título do link inválido.");
        }

        $this->title = $title;
    }

    public function render() {
        $settings = Array(
            "link_type" => $this->link_type,
            "url" => $this->url,
            "target" => $this->target,
            "title" => $this->title
        );
        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
        $this->twig = new Twig_Environment($this->twig_loader);
        return $this->twig->render("hotsite/content/menu_link.twig", Array("config" => config::$html_preload, "link_settings" => $settings));
    }

}
