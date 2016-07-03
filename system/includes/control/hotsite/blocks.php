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
        $sql_input = array($block['page_id'], $block['weight'], $block['width'], $block['float'], $block['background_image_repeat']);
        $fields = array("id_pagina", "weight", "width", "float", "background_image_repeat");
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
        if(!$this->id || !is_numeric($this->id)) {
            throw new Exception("O bloco não está carregado.");
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
        $block['background_color'] = "ff0000";
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
