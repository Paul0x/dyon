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
    /* Database Connection */

    private $conn;

    /* Hotsite Basic Structure */
    private $event_id;
    private $pages;
    private $event;
    private $status;
    private $last_change;
    private $id;
    private $database_info;
    private $variable_list = Array("text_color", "text_font", "title_color", "title_font", "background_image", "background_color", "background_repeat", "gallery_status", "contact_status", "schedule_status", "faq_status", "blog_status");

    /* Hotsite CSS Structure */
    private $text_color;
    private $text_font;
    private $title_color;
    private $title_font;
    private $background_color;
    private $background_image;
    private $background_repeat;

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
        $dumpinfo = array(
            "text_color" => "ffffff",
            "title_color" => "dddddd",
            "background_color" => "449900"
        );
        $this->conn = $conn;

        if (!is_null($event_id)) {
            $this->loadHotsite($event_id);
        }
    }

    public function loadHotsite($event) {
        if (is_array($event)) {
            if (!is_numeric($event['id'])) {
                throw new Exception("O identificador do evento está em formato inválido.", 1);
            }
        } else {
            if (!is_numeric($event)) {
                throw new Exception("O identificador do evento está em formato inválido.", 2);
            }
            $eventcontroller = new eventController();
            $event = $eventcontroller->loadEvent($event);
        }
        $fields = array("id", "id_evento", "status", "info", "data_alteracao");
        $this->conn->prepareselect("hotsite", $fields, "id_evento", $event['id']);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível executar a ação.", 3);
        }
        $hotsite_array = $this->conn->fetch;
        $this->setId($hotsite_array['id']);
        $variables = $this->loadHotsiteVariables($hotsite_array);
    }

    private function setId($id) {
        if (!is_numeric($id)) {
            throw new Exception("Identificador do hotsite em formato inválido.");
        }
        $this->id = $id;
    }

    private function loadHotsiteVariables(Array $hotsite_array) {
        try {
            $info = unserialize($hotsite_array['info']);
            $this->database_info = $info;
        } catch (Exception $ex) {
            throw new Exception("Não foi possível pegar as informações do site.");
        }

        if (is_array($info)) {
            foreach ($info as $index => $variable) {
                if (in_array($index, $this->variable_list)) {
                    $this->__set($index, $variable);
                }
            }
            return 2;
        } else {
            return 1;
        }
    }

    public function getHTMLConfigVariables($output_format = "json") {
        $variables = array("text_color", "text_font", "title_color", "title_font", "background_image", "background_color", "background_repeat");
        $hotsite_config = array();
        foreach ($variables as $index => $variable) {
            $hotsite_config[$variable] = $this->$variable;
        }

        if ($output_format == "json") {
            return json_encode($hotsite_config);
        } else {
            return $hotsite_config;
        }
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

    public function setHotsiteConfig($hotsite_config) {
        $hex_check = "/^[0-9A-F]{6}$/";
        $color_variables = array("text_color", "background_color", "title_color");
        foreach ($color_variables as $index => $variable) {
            if ($hotsite_config[$variable] != $this->$variable && preg_match($hex_check, $hotsite_config[$variable])) {
                $this->__set($variable, $hotsite_config[$variable]);
            }
        }

        if (isset($hotsite_config['background_image'])) {
            $hotsitefiles = new hotsiteFiles();
            $image_path = $hotsitefiles->saveBackgroundImage($this, $hotsite_config['background_image']);
            if ($this->background_image != "") {
                $old_background = $this->background_image;
            }
            $hotsitefiles->removeBackgroundImage($this, $old_background);
            $this->background_image = $image_path;
        }

        if ($hotsite_config['background_repeat'] != $this->background_repeat && ($hotsite_config['background_repeat'] == 0 || $hotsite_config['background_repeat'] == 1)) {
            $this->background_repeat = $hotsite_config["background_repeat"];
        }
    }

    public function save($mode) {
        if (!isset($this->database_info)) {
            throw new Exception("As informações do hotsite não estão carregadas.");
        }

        if (!is_numeric($this->id)) {
            throw new Exception("O identificador do hotsite não está disponível.");
        }

        foreach ($this->variable_list as $index => $variable) {
            if ($this->$variable != $this->database_info[$variable]) {
                $this->database_info[$variable] = $this->$variable;
            }
        }

        $serialized_info = serialize($this->database_info);
        $this->conn->prepareupdate($serialized_info, "info", "hotsite", $this->id, "id");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível salvar as informações do hotsite.");
        }
    }

    public function createCache() {
        
    }

    public function getId() {
        if (!is_numeric($this->id)) {
            throw new Exception("O identificador do hotsite é inválido.");
        }

        return $this->id;
    }

}
