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
 *  File: control.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/usuario/users.php");
require_once("includes/lib/Twig/Autoloader.php");
require_once("includes/control/hotsite/hotsite.php");
require_once("includes/control/hotsite/content/contents.php");
require_once("includes/control/hotsite/files.php");
include("includes/control/hotsite/render.php");

class hotsiteController2 {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    /**
     * Inicializa a classe de controle, quando chamada pela interface via browser.
     * @param Array $url
     */
    public function init($url) {
        Twig_Autoloader::register();
        try {
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
            $this->twig = new Twig_Environment($this->twig_loader);

            $this->usercontroller = new userController();
            if (!$this->usercontroller->authUser()) {
                header("location: " . HTTP_ROOT);
            }
            $user = $this->usercontroller->getUser();

            switch ($url[1]) {
                case "ajax":
                    $this->ajaxLoad($user);
                    break;
                default:
                    $this->loadHotsiteAdministrativePage($user);
                    break;
            }
        } catch (Exception $ex) {
            echo $this->twig->render("hotsite/main.twig", Array("error_flag" => $error_flag, "evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        }
    }

    private function loadHotsiteAdministrativePage($user) {
        try {
            if (isset($_SESSION['hotsitecache'])) {
                unset($_SESSION['hotsitecache']);
            }
            $selectedevent = $user->getSelectedEvent();
            $eventcontroller = new eventController();
            $evento = $eventcontroller->loadEvent($selectedevent);
            $events_select = $eventcontroller->listEvents(true, $user);
            $_SESSION['hotsitecache'] = serialize(new hotsite($this->conn, $evento));
            echo $this->twig->render("hotsite/main.twig", Array("evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            $error_flag = $error->getMessage();
            echo $this->twig->render("hotsite/main.twig", Array("error_flag" => $error_flag, "evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        }
    }

    private function ajaxLoad($user) {
        $mode = filter_input(INPUT_POST, "mode", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        switch ($mode) {
            case "get_hotsite_interface":
                $this->loadHotsiteInterface();
                break;
            case "load_hotsite_config_interface":
                $this->loadHotsiteConfigInterface("html");
                break;
            case "submit_hotsite_config":
                $this->changeHotisteConfig();
                break;
            case "get_hotsite_page":
                $this->getHotsitePage(true);
                break;
            case "create_block":
                $this->createHotsiteBlock();
                break;
            case "get_block_edit_form";
                $this->getBlockEditForm();
                break;
            case "remove_block":
                $this->removeBlock();
                break;
            case "submit_block_edit":
                $this->editBlock();
                break;
            case "update_block_weight":
                $this->updateBlockWeight();
                break;
            case "get_contents_create_types":
                $this->getAvailableContentTypes();
                break;
            case "load_block_content":
                $this->loadBlockContent();
                break;
            case "load_content_edit_interface":
                $this->loadContentEditForm();
                break;
        }
    }

    private function loadHotsiteInterface() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("Não foi possível carregar o hotsite.");
            }
            $interface_modules = Array();
            $interface_modules['topmenu'] = $this->twig->render("hotsite/topmenu.twig", Array("config" => config::$html_preload));
            echo json_encode(array("success" => "true", "modules" => $interface_modules, "hotsite" => array("id" => $hotsite->getId())));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadHotsiteConfigInterface($output) {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("Não foi possível carregar o hotsite.");
            }

            $hotsite_config = $hotsite->getHTMLConfigVariables("array");
            echo json_encode(array("success" => "true", "hotsite_config" => $hotsite_config, "html" => $this->twig->render("hotsite/ajax_config_interface.twig", Array("config" => config::$html_preload))));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function changeHotisteConfig() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("Não foi possível carregar o hotsite.");
            }

            $config_parameters = Array("text_color", "title_color", "background_color", "background_repeat", "background_image_remove");
            $hotsite_config = Array();
            foreach ($config_parameters as $index => $parameter) {
                $hotsite_config[$parameter] = filter_input(INPUT_POST, $parameter, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
            if ($_FILES['background_image']) {
                $hotsite_config['background_image'] = $_FILES['background_image'];
            }

            $hotsite->setHotsiteConfig($hotsite_config);
            $hotsite->save("config");
            $hotsite->createCache();
            $_SESSION['hotsitecache'] = serialize($hotsite);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function updateBlockWeight() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("Não foi possível carregar o hotsite.");
            }

            $page_id = filter_input(INPUT_POST, "page", FILTER_VALIDATE_INT);
            $block_weight = filter_input(INPUT_POST, "blocks", FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
            $page = $hotsite->getPageById($page_id);
            $page->updateBlockWeight($block_weight);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function getHotsitePage($current_hotsite = false) {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }

            $page_id = filter_input(INPUT_POST, "page_id", FILTER_VALIDATE_INT);
            if (!$page_id || $page_id <= 0) {
                $page_id = $hotsite->getFrontPageId();
            }

            $page = $hotsite->getPageById($page_id);
            $pageinfo['id'] = $page->getId();
            $pageinfo['render'] = $page->renderPage();
            $pageinfo['sidemenu'] = $this->twig->render("hotsite/sidemenu/page.twig", Array("config" => config::$html_preload));
            try {
                $pageinfo['blocks'] = $page->getPageBlocks();
            } catch (Exception $ex) {
                $pageinfo['blocks'] = null;
            }
            if ($current_hotsite) {
                $hotsite->setCurrentPage($page_id);
            }

            $_SESSION['hotsitecache'] = serialize($hotsite);
            echo json_encode(array("success" => "true", "page" => $pageinfo));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function createHotsiteBlock() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            $width = filter_input(INPUT_POST, "width", FILTER_VALIDATE_INT);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }
            $page = $hotsite->getPageById(CURRENT_PAGE);
            $block = $page->createBlock($width);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function getBlockEditForm() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }
            $page = $hotsite->getPageById(CURRENT_PAGE);
            $block = $page->getBlock($id);
            echo json_encode(array("success" => "true", "block" => $block, "html" => $this->twig->render("hotsite/ajax_blockedit_interface.twig", Array("block" => $block, "config" => config::$html_preload))));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function removeBlock() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }
            $page = $hotsite->getPageById(CURRENT_PAGE);
            $block = $page->getBlock($id, true);
            $block->removeBlock();
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editBlock() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }
            $page = $hotsite->getPageById(CURRENT_PAGE);

            $block_info['id'] = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $block_info['width'] = filter_input(INPUT_POST, "width", FILTER_VALIDATE_FLOAT);
            $block_info['background_color'] = filter_input(INPUT_POST, "background_color", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $block_info['background_image_repeat'] = filter_input(INPUT_POST, "background_repeat", FILTER_VALIDATE_INT);
            $block_info['background_image_remove'] = filter_input(INPUT_POST, "background_image_remove", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if ($_FILES['background_image']) {
                $block_info['background_image'] = $_FILES['background_image'];
            }
            $block = new block($block_info['id'], $page);
            $block->updateBlockInfo($block_info);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function getAvailableContentTypes() {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }
            $contentcontroller = new contentController($hotsite);
            $content_types = $contentcontroller->getAvailableContentTypes();
            echo json_encode(array("success" => "true", "content_types" => $content_types));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadBlockContent() {
        try {
            $block_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }

            $contentcontroller = new contentController($hotsite);
            $contents = $contentcontroller->getContentsByBlock($block_id, true);

            echo json_encode(array("success" => "true", "contents" => $contents));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadContentEditForm() {
        try {
            $content_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if (!is_object($hotsite) || !is_a($hotsite, "hotsite")) {
                throw new Exception("O Hotsite não está carregado.");
            }

            $contentcontroller = new contentController($hotsite);
            $content = $contentcontroller->getContentEditForm($content_id);

            echo json_encode(array("success" => "true", "content" => $content));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }
}

/**
 * Método para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_hotsite($url) {
    $eventcontroller = new hotsiteAdminController();
    $eventcontroller->init($url);
}
