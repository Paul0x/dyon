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
 *  File: finances.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/sql/finances.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/evento/pacotes.php");
require_once("includes/control/usuario/users.php");
require_once("includes/control/financeiro/compras.php");
require_once("includes/control/financeiro/flow.php");
require_once("includes/lib/Twig/Autoloader.php");

class financesController {

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

        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/manager');
        $this->twig = new Twig_Environment($this->twig_loader);

        $this->usercontroller = new userController();
        if (!$this->usercontroller->authUser(5)) {
            header("location: " . HTTP_ROOT);
        }

        switch ($url[1]) {
            case "compras":
                $this->interfaceListCompras($url);
                break;
            case "compra":
                $comprascontroller = new comprasController();
                $comprascontroller->loadCompraInterface($url[2], $this->twig, $this->usercontroller);
                break;
            case "ajax":
                $this->loadAjaxRequests();
                break;
            case "fluxo":
                $flowcontroller = new flowController($this->conn);
                $flowcontroller->init($this->twig, $url);
                break;
            default:
                $this->interfaceMainFinances($url);
                break;
        }
    }

    private function loadAjaxRequests() {
        switch ($_POST['mode']) {
            case "load_compras":
                $this->listCompras();
                break;
            case "add_categoria":
                $this->addCategoria();
                break;
            case "add_compra_form":
                $this->interfaceAddCompra();
                break;
            case "add_compra":
                $this->addCompra();
                break;
            case "edit_compra_status":
                $this->editCompraStatus();
                break;
            case "edit_compra_type":
                $this->editCompraType();
                break;
            case "edit_parcela":
                $this->editParcela();
                break;
            case "load_parcela_info":
                $this->loadParcelaInfo();
                break;
            case "confirm_parcela_submit":
                $this->confirmParcelaSubmit();
                break;
            case "cancel_parcela_submit":
                $this->cancelParcelaSubmit();
                break;
            case "edit_compra_quantity":
                $this->editCompraQuantity();
                break;
            case "add_parcela":
                $this->addParcela();
                break;
        }
    }

    private function interfaceMainFinances($url) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $eventcontroller = new eventController();
            $eventcontroller->setUser($user);
            $events_select = $eventcontroller->listEvents(array(), true);
            $this->loadFinancesSummary($user, $events_select);
        } catch (Exception $error) {
            echo $this->twig->render("financeiro/error_finances.twig", Array("finances__error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function loadFinancesSummary($user, $events_select) {

        if (!is_object($user) || !$user->getId()) {
            throw new Exception("Não foi possível obter usuário logado.");
        }

        $selected_event = $user->getSelectedEvent();
        $financemodel = new financeModel($this->conn);
        $summary = $financemodel->loadSummary($selected_event);

        $summary["pacotes_info"]["total_arrecadado"] = 0;
        $summary["pacotes_info"]["total_planejado"] = 0;
        $summary["pacotes_info"]["pacotes_validos"] = 0;
        $summary["pacotes_info"]["pacotes_aprovados"] = 0;
        $summary["pacotes_info"]["pacotes_quitados"] = 0;
        $datetime = new DateTime();
        $summary["date_now"] = $datetime->format("d/m/Y");

        foreach ($summary["pacotes"] as $index => $pacotes) {
            foreach ($pacotes["parcelas"] as $id => $parcela) {
                $summary["pacotes"][$index]["parcelas"][$id]["valor_parcelas"] = "R$" . number_format($parcela["valor_parcelas"], 2, ",", ".");
                switch ($parcela["status_parcela"]) {
                    case 0:
                        $summary["pacotes"][$index]["parcelas"][$id]["status_parcela_string"] = "Canceladas";
                        break;
                    case 1:
                        $summary["pacotes"][$index]["parcelas"][$id]["status_parcela_string"] = "Pendentes";
                        break;
                    case 2:
                        $summary["pacotes"][$index]["parcelas"][$id]["status_parcela_string"] = "Confirmadas";
                        break;
                }

                if ($pacotes["status_pacote"] != 0 && $pacotes["status_pacote"] != 1) {
                    if ($parcela["status_parcela"] == 2) {
                        $summary["pacotes_info"]["total_arrecadado"]+= $parcela["valor_parcelas"];
                        $summary["pacotes_info"]["total_planejado"]+= $parcela["valor_parcelas"];
                    } else if ($parcela["status_parcela"] == 1) {
                        $summary["pacotes_info"]["total_planejado"]+= $parcela["valor_parcelas"];
                    }
                }
            }

            switch ($pacotes["status_pacote"]) {
                case 0:
                    $summary["pacotes"][$index]["status_pacote_string"] = "Cancelados";
                    break;
                case 1:
                    $summary["pacotes"][$index]["status_pacote_string"] = "Pendentes";
                    $summary["pacotes_info"]["pacotes_validos"]+= $pacotes["num_pacotes"];
                    break;
                case 2:
                    $summary["pacotes"][$index]["status_pacote_string"] = "Aprovados";
                    $summary["pacotes_info"]["pacotes_validos"]+= $pacotes["num_pacotes"];
                    $summary["pacotes_info"]["pacotes_aprovados"]+= $pacotes["num_pacotes"];
                    break;
                case 3:
                    $summary["pacotes"][$index]["status_pacote_string"] = "Quitados";
                    $summary["pacotes_info"]["pacotes_validos"]+= $pacotes["num_pacotes"];
                    $summary["pacotes_info"]["pacotes_aprovados"]+= $pacotes["num_pacotes"];
                    $summary["pacotes_info"]["pacotes_quitados"]+= $pacotes["num_pacotes"];
                    break;
            }

            $summary["pacotes"][$index]["valor_total"] = "R$" . number_format($pacotes["valor_total"], 2, ",", ".");
        }
        $summary["evento_info"]["saldo_total"] = $summary["pacotes_info"]["total_arrecadado"] - $summary["compras"]["total_vencido"];
        $summary["pacotes_info"]["total_arrecadado"] = "R$" . number_format($summary["pacotes_info"]["total_arrecadado"], 2, ",", ".");
        $summary["evento_info"]["saldo_total"] = "R$" . number_format($summary["evento_info"]["saldo_total"], 2, ",", ".");
        $summary["pacotes_info"]["total_planejado"] = "R$" . number_format($summary["pacotes_info"]["total_planejado"], 2, ",", ".");

        $summary["compras"]['total_vencido'] = "R$" . number_format($summary["compras"]['total_vencido'], 2, ",", ".");
        $summary["compras"]['total_planejado'] = "R$" . number_format($summary["compras"]['total_planejado'], 2, ",", ".");

        foreach ($summary['list'] as $index => $list) {
            foreach ($list as $idx => $item) {
                $summary['list'][$index][$idx]['valor'] = "R$" . number_format($item['valor'], 2, ",", ".");
            }
        }

        echo $this->twig->render("financeiro/summary_finances.twig", Array("user" => $user->getBasicInfo(), "summary" => $summary, "events_select" => $events_select, "config" => config::$html_preload));
    }

    private function interfaceListCompras($url) {
        $usercontroller = new userController();
        $user = $usercontroller->getUser(DYON_USER_ADMIN);
        $eventcontroller = new eventController();
        $eventcontroller->setUser($user);
        $events_select = $eventcontroller->listEvents(array(), true);
        echo $this->twig->render("financeiro/list_compras_finances.twig", Array("user" => $user->getBasicInfo(), "events_select" => $events_select, "config" => config::$html_preload));
    }

    private function listCompras() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $comprascontroller = new comprasController();
            $compras = $comprascontroller->listCompras($user->getSelectedEvent());
            echo json_encode(array("success" => "true", "compras" => $compras));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addCategoria() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $nome = filter_input(INPUT_POST, "nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $comprascontroller = new comprasController();
            $comprascontroller->addCategoria($nome);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function interfaceAddCompra() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $comprascontroller = new comprasController();
            $categorias = $comprascontroller->getCategorias();

            echo json_encode(Array("success" => "true", "html" => $this->twig->render("financeiro/add_compra_finances.twig", Array("event_selected" => $user->getSelectedEvent(), "categorias" => $categorias))));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addCompra() {
        try {
            $compra["nome"] = filter_input(INPUT_POST, "nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $compra["tipo"] = filter_input(INPUT_POST, "tipo", FILTER_VALIDATE_INT);
            $compra["categoria"] = filter_input(INPUT_POST, "categoria", FILTER_VALIDATE_INT);
            $compra["quantidade"] = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);
            $compra["valor_unitario"] = filter_input(INPUT_POST, "valor_unitario", FILTER_VALIDATE_FLOAT);
            $compra["parcelas"] = filter_input_array(INPUT_POST, array("parcelas" => array('filter' => FILTER_SANITIZE_SPECIAL_CHARS, 'flags' => FILTER_REQUIRE_ARRAY)));
            $compra["parcelas"] = $compra["parcelas"]["parcelas"];
            $comprascontroller = new comprasController();
            $comprascontroller->addCompra($compra);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editCompraStatus() {
        try {
            $usercontroller = new userController();
            $usercontroller->getUser(DYON_USER_ADMIN);
            $compra_id = filter_input(INPUT_POST, "compra", FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);
            $comprascontroller = new comprasController();
            $comprascontroller->editCompraStatus($compra_id, $status);
            echo json_encode(array("success" => "true", "status" => $status));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editCompraType() {
        try {
            $usercontroller = new userController();
            $usercontroller->getUser(DYON_USER_ADMIN);
            $compra_id = filter_input(INPUT_POST, "compra", FILTER_VALIDATE_INT);
            $comprascontroller = new comprasController();
            $type = $comprascontroller->editCompraType($compra_id);
            echo json_encode(array("success" => "true", "tipo" => $type));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editCompraQuantity() {
        try {
            $usercontroller = new userController();
            $usercontroller->getUser(DYON_USER_ADMIN);
            $compra_id = filter_input(INPUT_POST, "compra", FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
            $comprascontroller = new comprasController();
            $comprascontroller->editQuantity($compra_id, $quantity);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editParcela() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $valor = filter_input(INPUT_POST, "valor", FILTER_VALIDATE_FLOAT);
            $vencimento = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $comprascontroller = new comprasController();
            $comprascontroller->editParcela($parcela_id, $vencimento, $valor);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addParcela() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $compra_id = filter_input(INPUT_POST, "compra", FILTER_VALIDATE_INT);
            $parcela['valor'] = filter_input(INPUT_POST, "valor", FILTER_VALIDATE_FLOAT);
            $parcela['vencimento'] = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $parcela['status'] = 1;
            $comprascontroller = new comprasController();
            $comprascontroller->addParcela($compra_id, $parcela, true);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadParcelaInfo() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $comprascontroller = new comprasController();
            $parcela = $comprascontroller->loadParcelaInfo($parcela_id);
            echo json_encode(array("success" => "true", "parcela" => $parcela));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
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
            $comprascontroller = new comprasController($this->conn);
            $parcela = $comprascontroller->parcelaConfirm($parcela_id, $comprovante, $tipo_comprovante);
            echo json_encode(Array("success" => "true", "parcela_id" => $parcela['id'], "data_pagamento" => $parcela['data_pagamento']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function cancelParcelaSubmit() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $comprascontroller = new comprasController($this->conn);
            $comprascontroller->parcelaCancel($parcela_id);
            echo json_encode(Array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    public function getReceitaByDate($id_event, $day = true, $week = false, $total = false) {
        if (!is_numeric($id_event)) {
            throw new Exception("Informações inválidas para captar número de vendas.");
        }

        try {
            $flowmodel = new flowModel($this->conn);
            $financemodel = new financeModel($this->conn);
            if ($day) {
                $datetime = new DateTime();
                $pacotes['day']['r'] = $flowmodel->getReceitaDia($datetime, $id_event);
                $datetime = new DateTime();
                $pacotes['day']['d'] = $flowmodel->getDespesaDia($datetime, $id_event);
                $pacotes['day']['t'] = number_format($pacotes['day']['r']['p'] - $pacotes['day']['d']['t'], 2, ",", ".");
            }
            if ($week) {
                $datetime = new DateTime();
                $pacotes['week']['r'] = $flowmodel->getReceitaSemana($datetime, $id_event);
                $datetime = new DateTime();
                $pacotes['week']['d'] = $flowmodel->getDespesaSemana($datetime, $id_event);
                $pacotes['week']['t'] = number_format($pacotes['week']['r']['p'] - $pacotes['week']['d']['t'], 2, ",", ".");
            }
            if ($total) {
                $datetime = new DateTime();
                $pacotes['total']['r'] = $financemodel->getReceita($id_event);
                $receita = 0;
                foreach ($pacotes['total']['r'] as $idx => $receitas) {
                    if ($receitas['status_pacote'] > 1) {
                        foreach ($receitas['parcelas'] as $i => $parcela) {
                            if ($parcela['status_parcela'] == 2) {
                                $receita += $parcela['valor_parcelas'];
                            }
                        }
                    }
                }
                $datetime = new DateTime();
                $pacotes['total']['d'] = $financemodel->getDespesa($id_event, $datetime);
                $despesa = $pacotes['total']['d']['total_vencido'];
                $pacotes['total']['t'] = number_format($receita - $despesa, 2, ",", ".");
            }
            return $pacotes;
        } catch (Exception $ex) {
            throw new Exception("Não foi possível contabilizar a receita do evento.");
        }
    }

}

/**
 * Método para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_finances($url) {
    $financescontroller = new financesController();
    $financescontroller->init($url);
}
