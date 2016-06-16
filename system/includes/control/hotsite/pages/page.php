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
            echo $ex->getMessage();
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

        $this->conn->prepareselect("bloco", array_merge(array("id"), block::getFieldList()), "id_pagina", $this->id, "", "", "", PDO::FETCH_ASSOC, "all", "weight");
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

    public function renderPage() {
        $page['inline_css'] = $this->hotsite->renderCss();
        $page['title'] = $this->page_title;
        $page['id'] = $this->id;
        $page['hotsite_id'] = $this->hotsite->getId();
        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/hotsite/render');
        $this->twig = new Twig_Environment($this->twig_loader);
        return $this->twig->render("body.twig", Array("config" => config::$html_preload, "page" => $page));
    }

    public function createBlock() {
        if (!is_numeric($this->id)) {
            throw new Exception("A página não está carregada.");
        }

        $block = block::setNewBlock($this);
    }

    public function getPageLastBlockWeight() {
        return 100;
    }

    public function getId() {
        if (!isset($this->id) || !is_numeric($this->id)) {
            throw new Exception("Página não instanciada.");
        }

        return $this->id;
    }

}
