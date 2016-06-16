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
    
    private $id;
    private $page_id;
    private $conn;
    private $data_alteracao;
    private $weight;
    private $width;
    
    
    public function __construct($block_id = null, &$page = null) {
        $this->conn = new conn();
   
    }
    
    private function setDatabaseInfo($block) {
        $sql_input = array($block['page_id'],$block['weight'],$block['width'],$block['float']);
        $fields = array("id_pagina","weight","width","float");
        $this->conn->prepareinsert("bloco", $sql_input, $fields);
        if(!$this->conn->executa()) {
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
        if(!is_object($page) || !is_a($page,"page")) {
            throw new Exception("Página inválida para criação do bloco.");
        }
        
        $block['page_id'] = $page->getId();
        $block['weight'] = $page->getPageLastBlockWeight();
        $block['width'] = 100;
        $block['float'] = true;  
        $block_obj->setInfo($block);
        return $block_obj;          
    }
    
}