<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: pagamento.php *  Type: Controller *  ===================================================================== *  */require_once(config::$syspath . "includes/sql/sqlcon.php");define(DYON_PAYMENT_DEPOSITO, 1);define(DYON_PAYMENT_BOLETO, 2);define(DYON_PAYMENT_PAGSEGURO, 3);class paymentController {    private $conn;    private $event_id;        /**     * Constrói o objeto e inicializa a conexão com o banco de dados.     */    public function __construct() {        $this->conn = new conn();    }        public function setEventId($event_id) {        if(!is_numeric($event_id)) {            throw new Exception("O identificador do evento é inválido.");        }                $this->event_id = $event_id;    }        public function getEventPaymentMethods() {        if(!$this->event_id) {            throw new Exception("Evento não carregado.");        }                $fields = array("id", "id_evento", "nome", "tipo", "hash_link", "max_parcelas", "taxa_servico", "taxa_dyon", "delay");        $this->conn->prepareselect("metodo_pagamento", $fields, "id_evento", $this->event_id, "same", "", "", PDO::FETCH_ASSOC, "all");        if(!$this->conn->executa()) {            throw new Exception("O evento não possui nenhum tipo de pagamento definido.");        }                return $this->conn->fetch;    }        public static function getAvailableAPIs() {        $payment_api_list = array(            DYON_PAYMENT_DEPOSITO => array(                "name" => "Depósito Bancário",                "file" => "api/deposito",                "default_service_tax" => "0",                "default_dyon_tax" => "1",                "default_delay" => "1"            ),            DYON_PAYMENT_BOLETO => array(                "name" => "Boleto Bancário",                "file" => "api/boleto",                "default_service_tax" => "0",                "default_dyon_tax" => "1",                "default_delay" => "2"            ),            DYON_PAYMENT_PAGSEGURO => array(                "name" => "Pagseguro",                "file" => "api/pagseguro",                "default_service_tax" => "7",                "default_dyon_tax" => "2",                "default_delay" => "30"            ),                    );                return $payment_api_list;    }        }