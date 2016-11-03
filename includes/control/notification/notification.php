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
 *  File: notification.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/control/controle/control.php");
require_once("includes/control/usuario/users.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/evento/pacotes.php");

class notificationController {

    private $pagseguro_token = "2BA8E426CAB645D7A32D65DFD533BC56";

    /**
     * ConstrÃ³i o objeto e inicializa a conexÃ£o com o banco de dados.
     */
    public function __construct() {
        
    }

    /**
     * Inicializa a API para lidar com as requisiÃ§Ãµes do usuÃ¡rio
     * @param Array $url
     */
    public function init($url) {
        switch ($url[1]) {
            default:
                $this->requestNotFound();
                break;
            case "pagseguro":
                $this->loadPagseguroNotification();
                break;
        }
    }

    private function requestNotFound() {
        echo json_encode(Array("success" => "false", "error" => "RequisiÃ§Ã£o nÃ£o encontrada na API de notificaÃ§Ãµes."));
    }

    private function loadPagseguroNotification() {
        try {
            $notificationCode = filter_input(INPUT_POST, "notificationCode");
            $notificationType = filter_input(INPUT_POST, "notificationType");
            $testes = fopen("includes/control/notification/pagseguro_testes.txt", "a");
            $text = $notificationCode . " - " . $notificationType . " \n";
            fwrite($testes, $text);
            if ($notificationType == "transaction") {
                $url = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/' . $notificationCode . '?email=paul.0@live.de&token=' . $this->pagseguro_token;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                fwrite($testes, $response);
                curl_close($ch);
                $xml_pagseguro = simplexml_load_string($response);
                $pagamento_pagseguro = json_decode(json_encode($xml_pagseguro));
                if ($pagamento_pagseguro->status == 3 && \strlen($pagamento_pagseguro->code) === 36) {
                    $usercontroller = new userController();
                    $pacotecontroller = new pacoteController(new conn());
                    $user_id = $usercontroller->getUserIdByEmail($pagamento_pagseguro->sender->email);
                    $user = $usercontroller->getUser(0,false);
                    $user->setId($user_id);
                    $user->setInfo();
                    $user_pacotes = $pacotecontroller->listPacotesByUserId($user);
                    $pacote_selected = null;
                    foreach($user_pacotes as $index => $pacote) {
                        if($pacote['id_evento'] == 1 && $pacote['tipo_pagamento'] == 2 && $pacote['status_pacote'] == 1) {
                            $pacote_selected = $pacote;
                        }
                    }                    
                    if(is_null($pacote_selected)) {
                        throw new Exception("Nenhum pacote encontrado para confirmação.");
                    }                    
                    $parcelas = $pacotecontroller->getParcelas($pacote_selected['id_pacote']);
                    $parcela_select = $parcelas[0]['id'];                    
                    $pacotecontroller->parcelaConfirm($parcela_select, $pagamento_pagseguro->code, 2, 2, $user);       
                } else {
                    throw new Exception("Pagamento não confirmado.");
                }
            } else {
                throw new Exception("Requisição inválida.");
            }
            fclose($testes);
            echo json_encode(Array("success" => "true", "notification" => "recieved"));
        } catch (Exception $ex) {
            fwrite($testes, "<br>".$ex->getMessage()."<br>");
            fclose($testes);
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

}

/**
 * MÃ©todo para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_notification($url) {
    $notificationcontroller = new notificationController();
    $notificationcontroller->init($url);
}
