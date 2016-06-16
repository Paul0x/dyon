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

    public function __construct($block_id = null, &$page = null, $block_info) {
        $this->conn = new conn();

        if (is_numeric($block_id) && is_object($page) && is_a($page, "page")) {
            $this->setInfo($block_id, $page, $block_info);
        }
    }
    
    public function __set($name, $value) {        
        $hex_check = "/^[0-9A-F]{6}$/";
        switch($name) {
            case "weight":
            case "width":    
                if(!is_numeric($value)) {
                    return false;
                }
                break;
            case "background_image_repeat":
            case "float":
                if($value != 1 && $value != 0) {
                    return false;
                }
                break;
            case "background_color":
                if(!preg_match($hex_check, $value)) {
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
        
        if(!is_numeric($block_id)) {
            throw new Exception("Identificador do bloco inválido.");
        }
        
        if(!is_a($page, "page")) {
            throw new Exception("Página inválida.");
        }
        
        $this->id = $block_id;
        $this->page_id = $page->getId();
        
        foreach(block::$field_list as $index => $variable) {
            if(isset($block_info[$variable])) {
                if(!$this->__set($variable,$block_info[$variable])) {
                    throw new Exception("O campo $variable está em formato inválido.");
                }
            }
        }        
    }

    private function setDatabaseInfo($block) {
        $sql_input = array($block['page_id'], $block['weight'], $block['width'], $block['float']);
        $fields = array("id_pagina", "weight", "width", "float");
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

    public static function setNewBlock(&$page) {
        $block_obj = new block();
        if (!is_object($page) || !is_a($page, "page")) {
            throw new Exception("Página inválida para criação do bloco.");
        }

        $block['page_id'] = $page->getId();
        $block['weight'] = $page->getPageLastBlockWeight();
        $block['width'] = 100;
        $block['float'] = true;
        $block_obj->setInfo($block);
        return $block_obj;
    }

    public function getInfo() {
        if (!is_numeric($this->id)) {
            throw new Exception("Bloco inválido.");
        }

        $info_array = array();
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

}
