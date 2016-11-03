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

require_once("includes/sql/flow.php");
require_once("includes/lib/Twig/Autoloader.php");

class flowController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Inicializa a classe de controle, quando chamada pela interface via browser.
     * @param Array $url
     */
    public function init($twig, $url) {
        $this->twig = $twig;

        $this->usercontroller = new userController();
        if (!$this->usercontroller->authUser()) {
            header("location: " . HTTP_ROOT);
        }

        $this->user = $this->usercontroller->getUser(5);
        if (!$url['ajax']) {
            switch ($url[2]) {
                default:
                    $this->loadCashFlowInterface();
                    break;
            }
        } else {
            switch ($_POST['mode']) {
                case "loadFlowSettings":
                    $this->loadFlowSettings();
                    break;
                case "loadDailyFlow":
                    $this->loadDailyFlow();
                    break;
                case "loadMonthlyFlow":
                    $this->loadMonthlyFlow();
                    break;
                case "setFlowInterface":
                    $this->setFlowInterface();
                    break;
                case "loadFlowDesc":
                    $this->loadFlowDesc();
                    break;
            }
        }
    }

    private function loadCashFlowInterface() {
        try {
            $eventcontroller = new eventController();
            $events_select = $eventcontroller->listEvents(array(), true);
            $event_selected = $this->user->getSelectedEvent();
            echo $this->twig->render("financeiro/cashflow.twig", Array("user" => $this->user->getBasicInfo(), "events_select" => $events_select, "config" => config::$html_preload, "selected_event" => $event_selected));
        } catch (Exception $error) {
            echo $this->twig->render("financeiro/error_finances.twig", Array("finances__error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function loadFlowSettings() {
        try {
            $flow_interface = $this->user->getSelectedFlow();
            echo json_encode(array("success" => "true", "flow" => $flow_interface));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function setFlowInterface() {
        try {
            $interface = filter_input(INPUT_POST, "interface", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            switch ($interface) {
                case 'day':
                    $flow_interface = 1;
                    break;
                case 'month':
                    $flow_interface = 2;
                    break;
                case 'year':
                    $flow_interface = 3;
                    break;
                default:
                    throw new Exception("Interface Inválida.");
            }
            $this->user->setSelectedFlow($flow_interface);
            echo json_encode(array("success" => "true", "flow" => $flow_interface));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadDailyFlow() {
        try {
            $date['d'] = filter_input(INPUT_POST, "day", FILTER_VALIDATE_INT);
            $date['m'] = filter_input(INPUT_POST, "month", FILTER_VALIDATE_INT);
            $date['y'] = filter_input(INPUT_POST, "year", FILTER_VALIDATE_INT);
            if (!checkdate($date['m'], $date['d'], $date['y'])) {
                $datetime = new DateTime();
            } else {
                $datetime = DateTime::createFromFormat("d/m/Y", $date['d'] . "/" . $date['m'] . "/" . $date['y']);
            }
            $flow['date'] = $datetime->format("d/m/Y");

            $selected_event = $this->user->getSelectedEvent();

            $flowmodel = new flowModel($this->conn);
            $datetime->modify("-1 Day");
            $saldo_anterior['r'] = $flowmodel->getReceitaAnterior($datetime, $selected_event);
            $saldo_anterior['d'] = $flowmodel->getDespesaAnterior($datetime, $selected_event);
            $saldo_anterior['t'] = number_format($saldo_anterior['r']['t'] - $saldo_anterior['d']['t'], 2, ",", ".");

            $flow['table'][] = Array(
                'tipo' => 'SA',
                'desc' => "Saldo até " . $datetime->format("d/m/Y"),
                'valor' => $saldo_anterior['t'],
                'full' => $saldo_anterior
            );

            $datetime->modify("+1 Day");
            $saldo_dia['r'] = $flowmodel->getReceitaDia($datetime, $selected_event);
            $saldo_dia['d'] = $flowmodel->getDespesaDia($datetime, $selected_event);
            $saldo_dia['t'] = number_format($saldo_dia['r']['t'] - $saldo_dia['d']['t'], 2, ",", ".");
            $flow['table'][] = Array(
                'tipo' => 'R',
                'desc' => "Receita Pacotes em " . $datetime->format("d/m/Y"),
                'valor' => number_format($saldo_dia['r']['t'], 2, ",", "."),
                'id' => "row-d-r-" . $datetime->format("d/m/Y")
            );
            $flow['table'][] = Array(
                'tipo' => 'D',
                'desc' => "Despesa Compras em " . $datetime->format("d/m/Y"),
                'valor' => number_format($saldo_dia['d']['t'], 2, ",", "."),
                'id' => "row-d-d-" . $datetime->format("d/m/Y")
            );
            $flow['table'][] = Array(
                'tipo' => 'SO',
                'desc' => "Saldo Operacional em " . $datetime->format("d/m/Y") . " ( R - D = SO )",
                'valor' => number_format($saldo_dia['r']['t'] - $saldo_dia['d']['t'], 2, ",", ".")
            );
            $flow['table'][] = Array(
                'tipo' => 'SAC',
                'desc' => "Saldo Acumulado em " . $datetime->format("d/m/Y") . " ( SO + SA = SAC )",
                'valor' => number_format(($saldo_dia['r']['t'] - $saldo_dia['d']['t']) + ($saldo_anterior['r']['t'] - $saldo_anterior['d']['t']), 2, ",", ".")
            );
            echo json_encode(array("success" => "true", "flow" => $flow));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadMonthlyFlow() {
        try {
            $date['m'] = filter_input(INPUT_POST, "month", FILTER_VALIDATE_INT);
            $date['y'] = filter_input(INPUT_POST, "year", FILTER_VALIDATE_INT);
            if (!checkdate($date['m'], 1, $date['y'])) {
                $datetime = new DateTime();
                $datetime->modify("first day of this month");
            } else {
                $datetime = DateTime::createFromFormat("d/m/Y", 1 . "/" . $date['m'] . "/" . $date['y']);
            }
            $selected_event = $this->user->getSelectedEvent();
            $eventcontroller = new eventController();
            $event = $eventcontroller->loadEvent($selected_event);
            $months_select = $this->getEventFlowMonths($event);
            $flow['date'] = $datetime->format("m/Y");
            $flowmodel = new flowModel($this->conn);
            $datetime->modify("-1 Day");
            $saldo_anterior['r'] = $flowmodel->getReceitaAnterior($datetime, $selected_event);
            $saldo_anterior['d'] = $flowmodel->getDespesaAnterior($datetime, $selected_event);
            $saldo_anterior['t'] = number_format($saldo_anterior['r']['t'] - $saldo_anterior['d']['t'], 2, ",", ".");

            $flow['table'][] = Array(
                'tipo' => 'SA',
                'desc' => "Saldo até " . $datetime->format("d/m/Y"),
                'valor' => $saldo_anterior['t'],
                'full' => $saldo_anterior,
                'id' => ""
            );

            $datetime->modify("+1 Day");
            $saldo_mes['r'] = $flowmodel->getReceitaMes($datetime, $selected_event);
            $saldo_mes['d'] = $flowmodel->getDespesaMes($datetime, $selected_event);
            $saldo_mes['t'] = number_format($saldo_mes['r']['t'] - $saldo_mes['d']['t'], 2, ",", ".");
            $flow['table'][] = Array(
                'tipo' => 'R',
                'desc' => "Receita Pacotes no mês de " . $this->translateMonth($datetime->format("m")),
                'valor' => number_format($saldo_mes['r']['t'], 2, ",", "."),
                'id' => "row-m-r-" . $datetime->format("m/Y")
            );
            $flow['table'][] = Array(
                'tipo' => 'D',
                'desc' => "Despesa Compras no mês de " . $this->translateMonth($datetime->format("m")),
                'valor' => number_format($saldo_mes['d']['t'], 2, ",", "."),
                'id' => "row-m-d-" . $datetime->format("m/Y")
            );
            $flow['table'][] = Array(
                'tipo' => 'SO',
                'desc' => "Saldo Operacional no mês de " . $this->translateMonth($datetime->format("m")) . " ( R - D = SO )",
                'valor' => number_format($saldo_mes['r']['t'] - $saldo_mes['d']['t'], 2, ",", "."),
                'id' => ""
            );
            $flow['table'][] = Array(
                'tipo' => 'SAC',
                'desc' => "Saldo Acumulado no mês de " . $this->translateMonth($datetime->format("m")) . " ( SO + SA = SAC )",
                'valor' => number_format(($saldo_mes['r']['t'] - $saldo_mes['d']['t']) + ($saldo_anterior['r']['t'] - $saldo_anterior['d']['t']), 2, ",", "."),
                'id' => ""
            );

            echo json_encode(array("success" => "true", "flow" => $flow, "select_array" => $months_select));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function getEventFlowMonths($event) {
        $datetime_inicio = new DateTime($event[5]);
        $datetime_fim = new DateTime($event['data_fim']);
        $interval = $datetime_fim->diff($datetime_inicio);
        $months = $interval->m + ($interval->y * 12);
        $select_array = Array();
        for ($i = 0; $i <= $months + 10; $i++) {
            if ($i != 0) {
                $datetime_inicio->modify("+1 Month");
            }
            $select_array[] = Array(
                "string" => $datetime_inicio->format("m/Y"),
                "value" => $datetime_inicio->format("m/Y")
            );
        }
        return $select_array;
    }

    private function translateMonth($month) {
        switch ($month) {
            case 1:
                return "Janeiro";
            case 2:
                return "Fevereiro";
            case 3:
                return "Março";
            case 4:
                return "Abril";
            case 5:
                return "Maio";
            case 6:
                return "Junho";
            case 7:
                return "Julho";
            case 8:
                return "Agosto";
            case 9:
                return "Setembro";
            case 10:
                return "Outubro";
            case 11:
                return "Novembro";
            case 12:
                return "Dezembro";
        }
    }

    private function loadFlowDesc() {
        try {
            $selected_event = $this->user->getSelectedEvent();
            $flow_type = filter_input(INPUT_POST, "flow", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $flow_transaction_type = filter_input(INPUT_POST, "type", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $flow_date = filter_input(INPUT_POST, "date", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $flowmodel = new flowModel($this->conn);
            switch ($flow_type) {
                case 'm':
                    $flow_date = explode("/", $flow_date);
                    $month = $flow_date[0];
                    $year = $flow_date[1];
                    if ($flow_transaction_type != "r" && $flow_transaction_type != 'd') {
                        throw new Exception("Tipo de transação incorreta.");
                    }
                    $date = new DateTime($year . "-" . $month . "-01 00:00:00");
                    switch ($flow_transaction_type) {
                        case 'r':
                            $transactions = $flowmodel->getFlowMonthDescReceita($date, $selected_event);
                            break;
                        case 'd':
                            $transactions = $flowmodel->getFlowMonthDescDespesa($date, $selected_event);
                            break;
                    }
                    break;
                case 'd':
                    $flow_date = explode("/", $flow_date);
                    $day = $flow_date[0];
                    $month = $flow_date[1];
                    $year = $flow_date[2];
                    if ($flow_transaction_type != "r" && $flow_transaction_type != 'd') {
                        throw new Exception("Tipo de transação incorreta.");
                    }
                    $date = new DateTime($year . "-" . $month . "-$day 00:00:00");
                    switch ($flow_transaction_type) {
                        case 'r':
                            $transactions = $flowmodel->getFlowMonthDescReceita($date, $selected_event);
                            break;
                        case 'd':
                            $transactions = $flowmodel->getFlowMonthDescDespesa($date, $selected_event);
                            break;
                    }
                    break;
            }
            foreach ($transactions as $index => $type) {
                $transactions[$index]['v'] = 0;
                $transactions[$index]['c'] = 0;
                foreach ($type['t'] as $i => $transaction) {
                    $transactions[$index]['v'] += $transaction['valor'];
                    $transactions[$index]['c'] ++;
                    $transactions[$index]['t'][$i]['valor'] = "R$" . number_format($transaction['valor'], 2, ",", ".");
                }
                $transactions[$index]['v'] = number_format($transactions[$index]['v'], 2, ",", ".");
            }
            echo json_encode(array("success" => "true", "transactions" => $transactions));
        } catch (Exeption $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

}
