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
 *  File: control.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/sql/control.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/evento/pacotes.php");
require_once("includes/control/usuario/users.php");
require_once("includes/control/controle/cliente_overview.php");
require_once("includes/lib/Twig/Autoloader.php");

class controlController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    /**
     * Inicializa a classe de controle, quando chamada pela interface via browser.
     * @param Array $url
     */
    public function init($url) {
        Twig_Autoloader::register();

        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
        $this->twig = new Twig_Environment($this->twig_loader);

        $this->usercontroller = new userController();
        if (!$this->usercontroller->authUser()) {
            header("location: " . HTTP_ROOT);
        } else {
            switch ($url[0]) {
                case "cliente":
                    $this->clienteOverview = new clienteOverviewController($url, $this->twig, $this->usercontroller);
                    break;
                default:
                    switch ($url[1]) {
                        case "pacote":
                        case "":
                            $this->interfaceMainPacote($url);
                            break;
                        case "cliente":
                            $this->interfaceMainCliente($url);
                            break;
                        case "upload":
                            switch ($_POST['mode']) {
                                case "confirm_parcela_submit":
                                    $this->confirmParcela();
                                    break;
                            }
                            break;
                        case "ajax":
                            switch ($_POST['mode']) {
                                case "change_event":
                                    $this->changeAdminEvent();
                                    break;
                                case "search_cliente_id":
                                    $this->searchClienteId();
                                    break;
                                case "get_groups_select":
                                    $this->getGroupsSelect();
                                    break;
                                case "get_max_parcelas":
                                    $this->getMaxParcelas();
                                    break;
                                case "change_pacote_status":
                                    $this->changePacoteStatus();
                                    break;
                                case "get_pacote_parcelas":
                                    $this->getPacoteParcelas();
                                    break;
                                case "parcela_confirm_form":
                                    $this->loadParcelaConfirmForm();
                                    break;
                                case "confirm_parcela_submit":
                                    $this->confirmParcelaSubmit();
                                    break;
                                case "cancel_parcela_submit":
                                    $this->cancelParcelaSubmit();
                                    break;
                                case "add_parcela_form":
                                    $this->addParcelaForm();
                                    break;
                                case "add_parcela_submit":
                                    $this->addParcelaSubmit();
                                    break;
                                case "parcela_comprovante_form":
                                    $this->loadParcelaComprovante();
                                    break;
                                case "edit_parcela_submit":
                                    $this->editParcelaSubmit();
                                    break;
                                case "load_grupo_info":
                                    $this->loadGrupoInfo();
                                    break;
                                case "edit_grupo":
                                    $this->editGroup();
                                    break;
                                case "pacote_load_info":
                                    $this->loadPacoteInfo();
                                    break;
                                case "pacote_edit_info":
                                    $this->pacoteEditInfo();
                                    break;
                                case "check_pacotes_checkin":
                                    $this->checkPacotesCheckIn();
                                    break;
                                case "checkin_submit":
                                    $this->checkInPacotes();
                                    break;
                            }
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Chama a página inicial do controle de clientes
     * @param Array $url
     */
    private function interfaceMainPacote($url) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $eventcontroller = new eventController();
            $events_select = $eventcontroller->listEvents(true, $user);
            switch ($url[2]) {
                case "":
                    $this->interfaceListPacote($url, $user, $usercontroller, $events_select);
                    break;
                case "add":
                    $this->interfaceAddPacote($user, $events_select);
                    break;
                case "grupos":
                    $this->interfaceListGrupos($url, $user, $usercontroller, $events_select);
                    break;
                case "parcelas":
                    $this->interfaceListParcelas($url, $user, $usercontroller, $events_select);
                    break;
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("control_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    /**
     * Chama a página inicial do controle de pacotes
     * @param Array $url
     */
    private function interfaceListPacote($url, $user, $usercontroller, $events_select) {
        try {
            $user->setConn($this->conn);

            $filters = Array();
            $filters['page'] = filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT);
            $filters['field']['label'] = filter_input(INPUT_POST, 'fieldquery', FILTER_SANITIZE_ENCODED);
            $filters['field']['value'] = filter_input(INPUT_POST, 'querystring', FILTER_SANITIZE_SPECIAL_CHARS);
            $filters['status'] = filter_input(INPUT_POST, 'query_status', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
            $filters['pagamento'] = filter_input(INPUT_POST, 'query_pagamento', FILTER_VALIDATE_INT);
            $filters['lote'] = filter_input(INPUT_POST, 'query_lote', FILTER_VALIDATE_INT);
            $filters['order'] = json_decode(filter_input(INPUT_POST, 'order'), true);
            if ($filters['field']['label'] == NULL) {
                $filters['field']['label'] = "nome";
                $filters['field']['value'] = "";
            }

            $filters['id_evento'] = $user->getSelectedEvent();
            $pacotecontroller = new pacoteController($this->conn);

            $search['query_searched'] = $filters['field']['value'];
            $search['field_searched'] = $filters['field']['label'];
            $search['status_searched'] = json_encode($filters['status']);
            $search['pagamento_searched'] = $filters['pagamento'];
            $search['lote_searched'] = $filters['lote'];
            if (is_array($filters['order'])) {
                $search['order_json'] = json_encode($filters['order']);
            } else {
                $search['order_json'] = "";
            }

            try {
                $pacotes = $pacotecontroller->listPacotes($filters);
            } catch (Exception $error) {
                $error_flag = $error->getMessage();
            }

            $eventcontroller = new eventController();
            $evento = $eventcontroller->loadEvent($user->getSelectedEvent());
            if (!$url['ajax']) {
                echo $this->twig->render("controle/list_pacote_control.twig", Array("error_flag" => $error_flag, "evento" => $evento, "pacotes" => $pacotes, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo(), "search" => $search));
            } else {
                echo json_encode(Array(
                    "success" => "true",
                    "pacotes_html" => $this->twig->render("controle/list_pacote_list.twig", Array("config" => config::$html_preload, "pacotes" => $pacotes)),
                    "pacotes_count" => $pacotes['count']['query']
                ));
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("control_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function interfaceAddPacote($user, $events_select) {
        try {
            $eventcontroller = new eventController();
            $event = $eventcontroller->loadEvent($user->getSelectedEvent(), DYON_EVENT_STATUS_OPEN);

            /**
             *  Formas de Pagamento
             *  Array prévio para adição de pacotes, será necessário adicionar mais uma tabela ao banco de dados.
             */
            $event['formas_pagamento'] = Array(
                Array(
                    "id" => 1,
                    "nome" => "Boleto Bancário"
                ),
                Array(
                    "id" => 2,
                    "nome" => "Pagseguro"
                ),
                Array(
                    "id" => 3,
                    "nome" => "Depósito"
                )
            );
            try {
                if (isset($_POST['submit'])) {
                    $pacotecontroller = new pacoteController($this->conn);
                    $pacotecontroller->addPacote();
                    $confirm_edit_flag = true;
                }
            } catch (Exception $error) {
                $error_edit_flag = $error->getMessage();
            }
            echo $this->twig->render("controle/add_pacote_control.twig", Array("event" => $event, "confirm_edit_flag" => $confirm_edit_flag, "events_select" => $events_select, "error_edit_flag" => $error_edit_flag, "config" => config::$html_preload, "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("config" => config::$html_preload, "error" => $error->getMessage()));
        }
    }

    /**
     * Chama a página inicial do controle de grupos
     * @param Array $url
     */
    private function interfaceListGrupos($url, $user, $usercontroller, $events_select) {
        try {
            $user->setConn($this->conn);
            $filters['fieldquery'] = filter_input(INPUT_POST, "fieldquery", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $filters['querystring'] = filter_input(INPUT_POST, "querystring", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $filters['id_evento'] = $user->getSelectedEvent();
            $eventcontroller = new eventController();
            $evento = $eventcontroller->loadEvent($user->getSelectedEvent());
            $pacotecontroller = new pacoteController($this->conn);
            $grupos = $pacotecontroller->listPacoteByGroups($filters);
            if (!$url['ajax']) {
                echo $this->twig->render("controle/list_grupos_control.twig", Array("config" => config::$html_preload, "error_flag" => $error_flag, "evento" => $evento, "grupos" => $grupos, "events_select" => $events_select, "user" => $user->getBasicInfo()));
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("control_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function interfaceListParcelas($url, $user, $usercontroller, $events_select) {
        try {
            $user->setConn($this->conn);

            $filters['id_evento'] = $user->getSelectedEvent();
            $filters['status'] = $url[3];
            $filters['page'] = filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            try {
                $pacotes = $pacotecontroller->listPacotesByParcela($filters);
            } catch (Exception $error) {
                $error_flag = $error->getMessage();
            }
            $evento = $filters['id_evento'];
            if (!$url['ajax']) {
                echo $this->twig->render("controle/list_parcela_control.twig", Array("error_flag" => $error_flag, "evento" => $evento, "pacotes" => $pacotes, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
            } else {
                echo json_encode(Array(
                    "success" => "true",
                    "pacotes_html" => $this->twig->render("controle/list_parcela_list.twig", Array("config" => config::$html_preload, "pacotes" => $pacotes)),
                    "pacotes_count" => $pacotes['count']['query']
                ));
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("control_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    /**
     * Chama a página inicial do controle de clientes
     * @param Array $url
     */
    private function interfaceMainCliente($url) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            switch ($url[2]) {
                case "":
                    $this->interfaceListCliente($url, $user, $usercontroller);
                    break;
                case "add":
                    $this->interfaceAddCliente($user);
                    break;
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("control_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function interfaceListCliente($url, $user, $usercontroller) {
        try {
            $filters = Array();
            try {
                $eventcontroller = new eventController();
                $events_select = $eventcontroller->listEvents(true, $user);
            } catch (Exception $error) {
                $events_select = false;
            }
            $filters['page'] = filter_input(INPUT_POST, 'page', FILTER_VALIDATE_INT);
            $filters['field']['label'] = filter_input(INPUT_POST, 'fieldquery', FILTER_SANITIZE_ENCODED);
            $filters['field']['value'] = filter_input(INPUT_POST, 'querystring', FILTER_SANITIZE_SPECIAL_CHARS);
            if ($filters['field']['label'] == NUll) {
                $filters['field']['label'] = "nome";
            }
            try {
                $clientes = $usercontroller->listUsers($filters, 1);
            } catch (Exception $error) {
                $error_flag = $error->getMessage();
            }

            $search['query_searched'] = $filters['field']['value'];
            $search['field_searched'] = $filters['field']['label'];

            if (!$url['ajax']) {
                echo $this->twig->render("controle/list_cliente_control.twig", Array("error_flag" => $error_flag, "clientes" => $clientes, "events_select" => $events_select, "config" => config::$html_preload, "user" => $user->getBasicInfo(), "search" => $search));
            } else {
                echo json_encode(Array(
                    "success" => "true",
                    "clientes_html" => $this->twig->render("controle/list_cliente_list.twig", Array("config" => config::$html_preload, "clientes" => $clientes)),
                    "clientes_count" => $clientes['count']['query']
                ));
            }
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("error" => $error->getMessage(), "config" => config::$html_preload, "user" => $user->getBasicInfo()));
        }
    }

    private function interfaceAddCliente($user) {
        try {
            try {
                if (isset($_POST['submit'])) {
                    $usercontroller = new userController();
                    $usercontroller->addUser();
                    $confirm_edit_flag = true;
                }
                try {
                    $eventcontroller = new eventController();
                    $events_select = $eventcontroller->listEvents(true, $user);
                } catch (Exception $error) {
                    $events_select = false;
                }
            } catch (Exception $error) {
                $error_edit_flag = $error->getMessage();
            }
            echo $this->twig->render("controle/add_cliente_control.twig", Array("confirm_edit_flag" => $confirm_edit_flag, "events_select" => $events_select, "error_edit_flag" => $error_edit_flag, "config" => config::$html_preload, "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            echo $this->twig->render("controle/error_control.twig", Array("config" => config::$html_preload));
        }
    }

    private function changeAdminEvent() {
        try {
            $event_id = filter_input(INPUT_POST, "event", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);

            $user->setSelectedEvent($event_id);
            echo json_encode(Array("success" => "true"));
        } catch (Exception $error) {
            echo json_encode(Array("success" => "true", "error" => $error->getMessage()));
        }
    }

    private function searchClienteId() {
        try {
            $searched_name = filter_input(INPUT_POST, "name", FILTER_SANITIZE_SPECIAL_CHARS);
            $usercontroller = new userController();
            $names = $usercontroller->getUserIdByName($searched_name);
            echo json_encode(Array("success" => "true", "names" => $names));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false"));
        }
    }

    private function getGroupsSelect() {
        try {
            $event_id = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
            $this->controlmodel = new controlModel($this->conn);
            $groups = $this->controlmodel->listGroupsQuery($event_id);
            echo json_encode(Array("success" => "true", "groups" => $groups));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    public function getMaxParcelas() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $user_info = $user->getBasicInfo();

            $forma_pagamento = filter_input(INPUT_POST, "forma_pagamento", FILTER_VALIDATE_INT);
            $lote_id = filter_input(INPUT_POST, "lote_id", FILTER_VALIDATE_INT);
            $formas_pagamento = Array(1, 2, 3);
            if (!is_numeric($forma_pagamento) || !in_array($forma_pagamento, $formas_pagamento)) {
                throw new Exception("Selecione uma forma de pagamento válida.");
            }
            if ($user_info['tipo'] <= 1) {
                switch ($forma_pagamento) {
                    case 1:
                        $max_parcelas = 4;
                        break;
                    case 2:
                        $max_parcelas = 1;
                        break;
                    case 3:
                        $max_parcelas = 4;
                }
            } else {
                $max_parcelas = 12;
            }

            $this->conn->prepareselect("lote", "valor", "id", $lote_id);
            if (!$this->conn->executa() || $this->conn->rowcount != 1) {
                throw new Exception("Selecione um lote válido.");
            }

            $valor = $this->conn->fetch[0];
            $parcelas = Array();
            for ($i = 1; $i <= $max_parcelas; $i++) {
                $valor_i = $valor / $i;
                $parcelas[] = Array($i, $i . "x de R$" . number_format($valor_i, 2, ",", "."));
            }

            echo json_encode(Array("success" => "true", "parcelas" => $parcelas));
        } catch (Exception $error) {
            echo json_encode(Array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function changePacoteStatus() {
        try {
            $pacote_id = filter_input(INPUT_POST, "pacote_id", FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            $pacote_status = $pacotecontroller->changePacoteStatus($pacote_id, $status);
            echo json_encode(Array("success" => "true", "status" => $pacote_status));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function getPacoteParcelas() {
        try {
            $pacote_id = filter_input(INPUT_POST, "pacote_id", FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            $parcelas = $pacotecontroller->getParcelas($pacote_id);
            echo json_encode(Array("success" => "true", "parcelas" => $parcelas));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadParcelaConfirmForm() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            $parcela = $pacotecontroller->getParcelaById($parcela_id);
            echo json_encode(Array("success" => "true", "html" => $this->twig->render("controle/parcela_confirm_control.twig", Array("parcela" => $parcela, "config" => config::$html_preload))));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function confirmParcelaSubmit() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $tipo_comprovante = filter_input(INPUT_POST, "tipo_comprovante", FILTER_VALIDATE_INT);
            if ($tipo_comprovante == 1) {
                $comprovante = $_FILES['comprovante'];
            } else {
                $comprovante = filter_input(INPUT_POST, "comprovante", FILTER_SANITIZE_SPECIAL_CHARS);
            }
            $pacotecontroller = new pacoteController($this->conn);
            $parcela = $pacotecontroller->parcelaConfirm($parcela_id, $comprovante, 2, $tipo_comprovante);
            echo json_encode(Array("success" => "true", "pacote_id" => $parcela['id_pacote']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function cancelParcelaSubmit() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            $parcela = $pacotecontroller->changeParcelaStatus(0, $parcela_id);
            echo json_encode(Array("success" => "true", "pacote_id" => $parcela['id_pacote']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addParcelaForm() {
        try {
            echo json_encode(Array("success" => "true", "html" => $this->twig->render("controle/add_parcela_control.twig", Array("config" => config::$html_preload))));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addParcelaSubmit() {
        try {
            $pacote_id = filter_input(INPUT_POST, "pacote_id", FILTER_VALIDATE_INT);
            $valor = filter_input(INPUT_POST, "valor", FILTER_VALIDATE_FLOAT);
            $vencimento = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $pacotecontroller = new pacoteController($this->conn);
            $pacotecontroller->addParcela($valor, $vencimento, $pacote_id);
            echo json_encode(Array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editParcelaSubmit() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $valor = filter_input(INPUT_POST, "valor", FILTER_VALIDATE_FLOAT);
            $vencimento = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $pacotecontroller = new pacoteController($this->conn);
            $parcela = $pacotecontroller->editParcela($valor, $vencimento, $parcela_id);
            echo json_encode(Array("success" => "true", "parcela" => $parcela));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadParcelaComprovante() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);

            $pacotecontroller = new pacoteController($this->conn);
            $parcela = $pacotecontroller->getParcelaById($parcela_id);

            if (is_null($parcela["id_comprovante"])) {
                throw new Exception("Comprovante do pacote não encontrado.");
            }

            echo json_encode(Array("success" => "true", "comprovante" => $parcela["id_comprovante"], "tipo_comprovante" => $parcela['tipo_comprovante']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadGrupoInfo() {
        try {
            $grupo_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

            $grupocontroller = new grupoController($this->conn);
            $grupo = $grupocontroller->loadGroupInfo($grupo_id);

            echo json_encode(Array("success" => "true", "grupo" => $grupo));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editGroup() {
        try {
            $user = $this->usercontroller->getUser();
            $grupo['id'] = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $grupo['new_nome'] = filter_input(INPUT_POST, "edited_nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $grupo['new_lider'] = filter_input(INPUT_POST, "edited_lider", FILTER_VALIDATE_INT);
            $grupocontroller = new grupoController($this->conn);
            $grupo = $grupocontroller->editGroup($grupo);
            echo json_encode(Array("success" => "true", "grupo" => $grupo));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadPacoteInfo() {
        try {
            $user = $this->usercontroller->getUser();
            $id_pacote = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $pacotecontroller = new pacoteController($this->conn);
            $pacote = $pacotecontroller->loadPacoteById($id_pacote);
            $eventcontroller = new eventController();
            $event = $eventcontroller->loadEvent($user->getSelectedEvent(), DYON_EVENT_STATUS_OPEN);
            echo json_encode(Array("success" => "true", "pacote" => $pacote, "lotes" => $event['lista_lotes']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function pacoteEditInfo() {
        try {
            $user = $this->usercontroller->getUser();
            $infos['id'] = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $infos['lote'] = filter_input(INPUT_POST, "lote", FILTER_VALIDATE_INT);
            $infos['grupo'] = filter_input(INPUT_POST, "grupo", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $pacotecontroller = new pacoteController($this->conn);
            $pacote = $pacotecontroller->pacoteEditInfo($infos);
            echo json_encode(Array("success" => "true", "pacote" => $pacote));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function checkPacotesCheckIn() {
        try {
            $pacotes = filter_input(INPUT_POST, "pacotes", FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
            $pacotes_pendentes = array();

            $user = $this->usercontroller->getUser(DYON_USER_ADMIN);

            $pacotecontroller = new pacoteController($this->conn);
            if (!is_array($pacotes)) {
                throw new Exception("Nenhum pacote encontrado para cadastrar.");
            }
            foreach ($pacotes as $index => $id_pacote) {
                $pacote = $pacotecontroller->loadPacoteById($id_pacote);
                if ($pacote['status_pacote'] != 3) {
                    $parcelas = $pacotecontroller->getParcelas($id_pacote);
                    $pacotes_pendentes[$id_pacote]["nome"] = $pacote["nome_usuario"];
                    foreach ($parcelas as $idx => $parcela) {
                        if ($parcela['status'] == 1) {
                            $pacotes_pendentes[$id_pacote]["parcelas"][] = $parcela;
                        }
                    }
                }
            }

            echo json_encode(array("success" => "true", "count" => count($pacotes_pendentes), "pacotes" => $pacotes, "pacotes_pendentes" => $pacotes_pendentes));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function checkInPacotes() {
        try {
            $pacotes = filter_input(INPUT_POST, "pacotes", FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
            $user = $this->usercontroller->getUser(DYON_USER_ADMIN);

            if (!is_array($pacotes)) {
                throw new Exception("Nenhum pacote encontrado para cadastrar.");
            }
            $pacotecontroller = new pacoteController($this->conn);
            foreach ($pacotes as $index => $pacote_id) {
                $pacotecontroller->changePacoteStatus($pacote_id, 4);
            }
            
            $casas = $pacotecontroller->getPacoteByCasas($pacotes);

            echo json_encode(array("success" => "true", "casas" => $casas));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

}

/**
 * Método para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_control($url) {
    $eventcontroller = new controlController();
    $eventcontroller->init($url);
}
