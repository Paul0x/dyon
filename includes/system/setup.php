<?php/* * **************************************** *     _____                     *    |  __ \                   *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== */include("includes/lib/util.php");include("includes/system/config.php");class Setup {    private $ajax;    private $url;    public function init() {        $this->url = explode("/", filter_input(INPUT_GET, 'pt_cod'));        /* Check for AJAX requests         * PS: Check for HTTP_X_REQUESTED_WITH may not work for non-jquery requests. */        if ($_POST['majax'] == true && (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {            $this->url["ajax"] = true;        }                /* Defining GET parameters and loading the modules */        $this->module_array = "user_list";        if($this->url[0] == "vendor") {            $this->module_array = "vendor_list";            $this->url = array_splice($this->url, 1);        }                if (!in_array($this->url[0], config::$modules[$this->module_array])) {            $this->url[0] = "index";        }                $this->loadModules($this->url);    }    private function loadModules($urls) {        /* Carrega os módulos */        require_once(config::$modules[$urls[0]]["system_path"]);        if (isset(config::$modules[$urls[0]]["javascript"])) {            if (is_array(config::$modules[$urls[0]]["javascript"])) {                foreach (config::$modules[$urls[0]]["javascript"] as $scripts => $script) {                    config::$html_preload["javascript"][] = $script;                }            } else {                config::$html_preload["javascript"][] = config::$modules[$urls[0]]["javascript"];            }        }        if (isset(config::$modules[$urls[0]]["css"])) {            if (is_array(config::$modules[$urls[0]]["css"])) {                foreach (config::$modules[$urls[0]]["css"] as $styles => $style) {                    config::$html_preload["css"][] = $style;                }            } else {                config::$html_preload["css"][] = config::$modules[$urls[0]]["css"];            }        }        call_user_func(config::$modules[$urls[0]]["init_func"], $urls);    }}