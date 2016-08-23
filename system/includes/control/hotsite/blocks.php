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
 *  File: blocks.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

class block {

    private static $field_list = array("weight", "width", "float", "data_alteracao", "background_color", "background_image", "background_image_repeat");
    private $id;
    private $page_id;
    private $conn;
    private $data_alteracao;
    private $weight;
    private $width;

    public function __construct($block_id = null, &$page = null, $block_info = null) {
        $this->conn = new conn();

        if (is_numeric($block_id) && is_object($page) && is_a($page, "page")) {
            $this->setInfo($block_id, $page, $block_info);
        }
    }

    public function __set($name, $value) {
        $hex_check = "/^[0-9A-F]{6}$/";
        switch ($name) {
            case "weight":
            case "width":
                if (!is_numeric($value)) {
                    return false;
                }
                break;
            case "background_image_repeat":
            case "float":
                if ($value != 1 && $value != 0) {
                    return false;
                }
                break;
            case "background_color":
                if (!preg_match($hex_check, $value) && $value) {
                    return false;
                }
                break;
        }
        $this->$name = $value;
        return true;
    }

    private function setInfo($block_id, $page, $block_info = false) {
        if (!is_numeric($block_id)) {
            throw new Exception("Identificador do bloco inválido.");
        }

        if (!is_numeric($block_id)) {
            throw new Exception("Identificador do bloco inválido.");
        }

        if (!is_a($page, "page")) {
            throw new Exception("Página inválida.");
        }

        $this->id = $block_id;
        $this->page_id = $page->getId();


        if ($block_info) {
            foreach (block::$field_list as $index => $variable) {
                if (isset($block_info[$variable])) {
                    if (!$this->__set($variable, $block_info[$variable])) {
                        throw new Exception("O campo $variable está em formato inválido.");
                    }
                }
            }
        } else {
            $block_info = $this->getDatabaseInfo();
            if(!$block_info) {
                throw new Exception("Bloco não encontrado.");
            }
            $this->setInfo($block_id,$page,$block_info);
        }
        
    }

    public function getDatabaseInfo() {
        if (!$this->id || !$this->page_id) {
            throw new Exception("O identificador do bloco não está carregado para puxar informações.");
        }

        $infos = array_merge(array("id","id_pagina"), block::$field_list);
        $this->conn->prepareselect("bloco", $infos, "id", $this->id);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível encontrar o bloco.");
        }

        $block = $this->conn->fetch;
        if($block['id_pagina'] != $this->page_id) {
            throw new Exception("O bloco não pertece a página selecionada.");
        }
        
        return $block;
    }

    private function setDatabaseInfo($block) {
        $sql_input = array($block['page_id'], $block['weight'], $block['width'], $block['float'], $block['background_image_repeat'], $block['background_color']);
        $fields = array("id_pagina", "weight", "width", "float", "background_image_repeat", "background_color");
        $this->conn->prepareinsert("bloco", $sql_input, $fields);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível criar o bloco.");
        }

        $this->id = $this->conn->pegarMax("bloco");
        $this->page_id = $block['page_id'];
        $this->weight = $block['weight'];
        $this->width = $block['width'];
        $this->float = $block['float'];
        return $this;
    }
    
    public function updateBlockInfo($block_info) {
        $width_array = array (100,50,33.3,25,20,12.5);
        $changed_fields = array();
        if(!$this->id || !is_numeric($this->id)) {
            throw new Exception("O bloco não está carregado.");
        }
        
        if($block_info['background_color'] && $block_info['background_color'] != "remove") {
            $this->__set("background_color", $block_info['background_color']);
            $changed_fields[] = "background_color";
            $changed_values[] = $this->background_color;
        } else if($block_info['background_color'] == "remove") {
            $this->background_color = "remove";      
            $changed_fields[] = "background_color";
            $changed_values[] = 0;      
        }
        
        if($block_info['width'] != $this->width && in_array($block_info['width'], $width_array)) {
            $this->__set("width", $block_info['width']);            
            $changed_fields[] = "width";
            $changed_values[] = $this->width;
        }
        
        if($block_info['background_image']) {
            $this->setBackgroundImage($block_info['background_image']);
        } 
        
        
        $this->conn->prepareupdate($changed_values, $changed_fields, "bloco", $this->id, "id");
        if(!$this->conn->executa()) {
           throw new Exception("Não foi possível editar o bloco.");            
        }
    }

    public static function setNewBlock(&$page, $width = 100) {
        $block_obj = new block();
        if (!is_object($page) || !is_a($page, "page")) {
            throw new Exception("Página inválida para criação do bloco.");
        }

        if (!is_numeric($width) || ($width > 100 && $width <= 12.5)) {
            throw new Exception("Largura do bloco inválida.");
        }

        $block['page_id'] = $page->getId();
        $block['weight'] = $page->getPageLastBlockWeight();
        $block['width'] = $width;
        $block['float'] = 1;
        $block['background_image_repeat'] = 0;
        $block['background_color'] = "0";
        $block_obj->setDatabaseInfo($block);
        return $block_obj;
    }

    public function getInfo() {
        if (!is_numeric($this->id)) {
            throw new Exception("Bloco inválido.");
        }

        $info_array = array();
        $info_array['id'] = $this->id;
        $info_array['page_id'] = $this->page_id;
        $info_array['weight'] = $this->weight;
        $info_array['width'] = $this->width;
        $info_array['float'] = $this->float;
        $info_array['data_alteracao'] = $this->data_alteracao;
        $info_array['background_image'] = $this->background_image;
        $info_array['background_color'] = $this->background_color;
        $info_array['background_image_repeat'] = $this->background_image_repeat;

        return $info_array;
    }
    
    public function getId() {
        if (!is_numeric($this->id)) {
            throw new Exception("Bloco inválido.");
        }
        
        return $this->id;
    }

    public static function getFieldList() {
        return block::$field_list;
    }
    
    public function removeBlock() {
        if(!isset($this->id) || !is_numeric($this->id)) {
            throw new Exception("Identificador do bloco inválido.");
        }
        
        $this->conn->preparedelete("bloco", "id", $this->id);
        if(!$this->conn->executa()) {
            throw new Exception("Não foi possível remover o bloco.");
        }
        
    }

}
