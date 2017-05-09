<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: users_public.php *  Type: Controller *  ===================================================================== *  */require("users.php");class userPublicController extends user {    public function __construct() {        $this->conn = new conn();    }    /**     * Inicializa a classe de controle, quando chamada pela interface via browser.     * @param Array $url     */    public function init($url) {        $this->usercontroller = new userController();        switch ($url[1]) {            case "config":                $this->loadUserConfig();                break;            case "ajax":                $this->initAjax($url);                break;            case "logout":                $this->usercontroller->logoutUser();                header("location: " . HTTP_ROOT);                break;        }    }    private function initAjax($url) {        $method = filter_input(INPUT_POST, "mode", FILTER_SANITIZE_FULL_SPECIAL_CHARS);        switch ($method) {            case "load_menu":                $this->usercontroller->loadUserMenu();                break;            case "change_user_instance":                $this->usercontroller->changeUserInstance();                break;            case "upload_profile_image":                $this->uploadProfileImageForm();                break;            case "update_personal_info":                $this->updatePersonalInfo();                break;            case "load_instance_add_form":                $this->loadInstanceAddForm();                break;        }    }    public function uploadProfileImageForm() {        try {            $image = $this->usercontroller->uploadProfileImage($_FILES['image']);            echo json_encode(array("success" => "true", "image" => $image));        } catch (Exception $error) {            echo json_encode(array("success" => "false", "error" => $error->getMessage()));        }    }    public function loadUserConfig() {        try {            $this->user = $this->usercontroller->getUser();            Twig_Autoloader::register();            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/public');            $this->twig = new Twig_Environment($this->twig_loader);            if (!$this->user) {                throw new Exception("Usuário inválido.");            }            Setup::addJavascript("public/preferences");            $user_info = $this->user->getBasicInfo();            try {                $user_info['instances'] = $this->user->getUserInstances();            } catch (Exception $ex) {                $user_info['instances'] = false;            }            echo $this->twig->render('usuario/user_preferences.twig', array("user" => $user_info, "config" => config::$html_preload));        } catch (Exception $ex) {            echo $ex->getMessage();        }    }    public function updatePersonalInfo() {        try {            $personal_info = filter_input(INPUT_POST, "infos", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);            $user = $this->usercontroller->getUser();            $user->updateInfos($personal_info);            echo json_encode(array("success" => "true"));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    public function loadInstanceAddForm() {        try {            $user = $this->usercontroller->getUser();            $instancecontroller = new instanceController();            $form = $instancecontroller->loadInstanceAddForm($user);            echo json_encode(array("success" => "true", "html" => $form));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }}function init_module_users_public($url) {    $usercontroller = new userPublicController();    $usercontroller->init($url);}?>