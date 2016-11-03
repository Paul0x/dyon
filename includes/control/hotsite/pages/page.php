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
 *  File: page.php
 *  Type: Controller
 *  =====================================================================
 * 
 */
include("includes/control/hotsite/blocks.php");

class page {

    private $hotsite;
    private $id;
    private $admin_title;
    private $page_title;
    private $blocks;
    private $data_alteracao;
    private $tipo;

    public function __construct($page_id = null, &$hotsite = null) {
        $this->conn = new conn();
        if ($page_id != null) {
            $this->loadPage($page_id, $hotsite);
        }
    }

    public function loadPage($page_id, &$hotsite) {
        if (!is_numeric($page_id)) {
            throw new Exception("O identificador da página está incorreto.");
        }

        if (!is_object($hotsite)) {
            throw new Exception("Você não está em um hotsite.");
        }


        $fields = array("id", "id_hotsite", "tipo", "data_alteracao", "info");
        $this->conn->prepareselect("pagina", $fields, "id", $page_id);
        if (!$this->conn->executa()) {
            throw new Exception("A página não foi encontrada.");
        }

        $page = $this->conn->fetch;
        if ($page['id_hotsite'] != $hotsite->getId()) {
            throw new Exception("A página procurada não está disponível.");
        }

        $this->id = $page['id'];
        $this->data_alteracao = $page['data_alteracao'];
        $this->tipo = $page['tipo'];
        try {
            $this->blocks = $this->loadPageBlocks();
        } catch (Exception $ex) {
            $this->blocks = null;
        }

        $this->hotsite = $hotsite;
    }

    public function getPageBlocks() {
        if (is_null($this->blocks) || !is_array($this->blocks)) {
            throw new Exception("A página não tem blocos.");
        }

        $block_array = array();
        foreach ($this->blocks as $index => $block) {
            $block_array[] = $block->getInfo();
        }
        return $block_array;
    }

    public function loadPageBlocks() {
        if (!is_numeric($this->id)) {
            throw new Exception("A página não está carregada.");
        }

        $this->conn->prepareselect("bloco", array_merge(array("id"), block::getFieldList()), "id_pagina", $this->id, "", "", "", PDO::FETCH_ASSOC, "all", array("weight","DESC"));
        if (!$this->conn->executa()) {
            throw new Exception("Nenhum bloco encontrado na página.");
        }
        $block_list = $this->conn->fetch;
        $blocks = array();
        foreach ($block_list as $index => $block) {
            $blocks[$index] = new block($block['id'], $this, $block);
        }

        return $blocks;
    }
    
    public function updateBlockWeight($block_weight) {
        if(!is_numeric($this->id)) {
            throw new Exception("A página não está carregada.");
        }
        
        if (is_null($this->blocks) || !is_array($this->blocks)) {
            throw new Exception("A página não tem blocos.");
        }
        
        if(!is_array($block_weight)) {
            throw new Exception("Lista de novos pesos inválidos.");
        }
       
        if(count($this->blocks) != count($block_weight)-1) {
            throw new Exception("O número de blocos listados difere do número de blocos na página. -".count($this->blocks)." / ".count($block_weight));
        }
        
        foreach($this->blocks as $index => $block) {
            if(!in_array($block->getId(), $block_weight)) {
                throw new Exception("O bloco selecionado não existe na página.");
            }            
        }
        
        unset($block_weight[count($block_weight)-1]);
        $max_weight = count($block_weight);
        foreach($block_weight as $index => $id) {
            $this->conn->prepareupdate($max_weight, "weight", "bloco", array($id,$this->id), array("id","id_pagina"));
            if(!$this->conn->executa()) {
                throw new Exception("Não foi possível alterar o peso do bloco. - ".$id);
            }
            $max_weight--;
        }
        
    }

    public function renderPage() {
        $page['inline_css'] = $this->hotsite->renderCss();
        $page['title'] = $this->page_title;
        $page['id'] = $this->id;
        $page['hotsite_id'] = $this->hotsite->getId();
        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/hotsite/render');
        $this->twig = new Twig_Environment($this->twig_loader);
        return $this->twig->render("body.twig", Array("config" => config::$html_preload, "page" => $page));
    }

    public function createBlock($width = 100) {
        if (!is_numeric($this->id)) {
            throw new Exception("A página não está carregada.");
        }

        $block = block::setNewBlock($this, $width);
    }

    public function getPageLastBlockWeight() {
        return 100;
    }

    public function getBlock($id, $obj = false) {
        if (!is_numeric($id)) {
            throw new Exception("Identificador do bloco inválido.");
        }

        if (!is_numeric($this->id)) {
            throw new Exception("A página não está carregada.");
        }

        $block = new block($id, $this);

        if (!$obj) {
            return $block->getInfo();
        } else {
            return $block;
        }
    }

    public function getId() {
        if (!isset($this->id) || !is_numeric($this->id)) {
            throw new Exception("Página não instanciada.");
        }

        return $this->id;
    }

}
