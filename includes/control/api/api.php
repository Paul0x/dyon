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
 *  File: api.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/control/controle/control.php");
require_once("includes/control/usuario/users.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/evento/pacotes.php");

class apiController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        
    }

    /**
     * Inicializa a API para lidar com as requisições do usuário
     * @param Array $url
     */
    public function init($url) {
        switch ($_POST['mode']) {
            default:
                $this->requestNotFound();
                break;
            case "authCheck":
                $this->clienteAuthCheck();
                break;
            case "authUser":
                $this->clienteAuthUser();
                break;
            case "getUserPacotes":
                $this->pacoteList();
                break;
            case "logoutUser":
                $this->clienteLogout();
                break;
            case "editInfo":
                $this->clienteEditInfo();
                break;
            case "user_register":
                $this->clienteRegister();
                break;
            case "getLoteInfo":
                $this->pacoteGetLoteInfo();
                break;
            case "checkGroupByCod":
                $this->grupoCheckCode();
                break;
            case "registerPacote":
                $this->pacoteRegister();
                break;
            case "getPacoteInfo":
                $this->pacoteGetInfo();
                break;
            case "getMaxParcela":
                $this->pacoteGetMaxParcelas();
                break;
            case "getPacoteParcelas":
                $this->pacoteGetParcelas();
                break;
            case "parcelaConfirmSubmit":
                $this->pacoteConfirmParcela();
                break;
            case "getGroupMembers":
                $this->pacoteGetGroupMembers();
                break;
            case "recoveryPasswordSubmit":
                $this->clienteRecoveryPassword(1);
                break;
            case "recoveryPasswordChange":
                $this->clienteRecoveryPassword(2);
                break;
        }
    }

    private function requestNotFound() {
        echo json_encode(Array("success" => "false", "error" => "Requisição não encontrada na API."));
    }

    private function clienteAuthCheck() {
        $usercontroller = new userController();
        if ($usercontroller->authUser()) {
            $user = $usercontroller->getUser();
            echo json_encode(Array("success" => "true", "user" => $user->getBasicInfo()));
        } else {
            echo json_encode(Array("success" => "false"));
        }
    }

    private function clienteAuthUser() {
        $data = filter_input_array(INPUT_POST, array(
            'data' => array('flags' => FILTER_FORCE_ARRAY)
        ));

        try {
            $usercontroller = new userController();
            $usercontroller->loginUser($data["data"]["email"], $data["data"]["password"]);
            $user = $usercontroller->getUser();
            echo json_encode(array("success" => "true", "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function clienteLogout() {
        try {
            $usercontroller = new userController();
            $usercontroller->logoutUser();
            echo json_encode(array("success" => "true"));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function clienteEditInfo() {
        $data = filter_input_array(INPUT_POST, array(
            'data' => array('flags' => FILTER_FORCE_ARRAY)
        ));

        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $user->updateInfos($data['data']);
            echo json_encode(array("success" => "true", "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function clienteRegister() {
        try {
            $usercontroller = new userController();
            $usercontroller->addUser();
            echo json_encode(array("success" => "true"));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function clienteRecoveryPassword($mode) {
        try {
            if ($mode == 1) {
                $data = filter_input_array(INPUT_POST, array(
                    'data' => array('flags' => FILTER_FORCE_ARRAY)
                ));
                $email = $data['data']['email'];
                $usercontroller = new userController();
                $usercontroller->setRecoveryPoint($email);
            }
            else if($mode == 2) {
                $data = filter_input_array(INPUT_POST, array(
                    'data' => array('flags' => FILTER_FORCE_ARRAY)
                ));
                $code = $data['data']['cod'];
                $password = $data['data']['password'];
                $usercontroller = new userController();
                $usercontroller->recoveryUserPassword($code, $password);                
            }
            echo json_encode(array("success" => "true"));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }
   
    

    private function pacoteList() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $pacotecontroller = new pacoteController(new conn());
            $pacotes = $pacotecontroller->listPacotesByUserId($user);
            echo json_encode(array("success" => "true", "pacotes" => $pacotes));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteGetLoteInfo() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY, 'filters' => FILTER_VALIDATE_INT)
            ));
            $lote_id = $data['data']['lote'];
            $lotecontroller = new loteController(new conn());
            $lote = $lotecontroller->getLoteById($lote_id);
            echo json_encode(array("success" => "true", "lote" => $lote));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function grupoCheckCode() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY)
            ));

            $grupocontroller = new grupoController(new conn());
            $grupo = $grupocontroller->getGroupByCod($data['data']['cod']);
            echo json_encode(array("success" => "true", "grupo" => $grupo));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteRegister() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY)
            ));

            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $data = $data['data'];
            $pacote = Array(
                "evento" => 2,
                "lote" => $data['lote']['id'],
                "desconto" => 0,
                "cliente" => $user->getId(),
                "pagamento" => $data['forma_pagamento'],
                "parcelas" => 1
            );

            if ($data['forma_pagamento'] == 3) {
                $pacote['parcelas'] = $data['parcelas'];
            }

            if ($data['group_action'] == 'add') {
                $pacote['grupo_add'] = $data['group_name'];
            } elseif ($data['group_action'] == 'select') {
                $pacote['grupo_select'] = $data['group_object']['id'];
            } else {
                throw new Exception("Grupo inválido para o pacote.");
            }

            $pacotecontroller = new pacoteController(new conn());
            $pacote_id = $pacotecontroller->addPacote($pacote);
            echo json_encode(array("success" => "true", "pacote" => $pacote_id));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteGetInfo() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY)
            ));

            $usercontroller = new userController();
            $user = $usercontroller->getUser();
            $pacote_id = $data['data']['pacote_id'];
            $pacotecontroller = new pacoteController(new conn());
            $pacote = $pacotecontroller->loadPacoteById($pacote_id);
            if ($user->getId() != $pacote['id_usuario']) {
                throw new Exception("O usuário não tem permissão para visualizar esse pacote.");
            }

            echo json_encode(array("success" => "true", "pacote" => $pacote));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteGetMaxParcelas() {
        try {
            $controlcontroller = new controlController();
            $controlcontroller->getMaxParcelas();
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteGetParcelas() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY, 'filters' => FILTER_VALIDATE_INT)
            ));
            $pacote_id = $data['data']['pacote_id'];
            $pacotecontroller = new pacoteController(new conn());
            $parcelas = $pacotecontroller->getParcelas($pacote_id);
            echo json_encode(array("success" => "true", "parcelas" => $parcelas));
        } catch (Exception $error) {
            echo json_encode(array("success" => "false", "error" => $error->getMessage()));
        }
    }

    private function pacoteConfirmParcela() {
        try {
            $parcela_id = filter_input(INPUT_POST, "parcela_id", FILTER_VALIDATE_INT);
            $comprovante = $_FILES['comprovante'];
            $pacotecontroller = new pacoteController(new conn());
            $parcela = $pacotecontroller->parcelaConfirm($parcela_id, $comprovante, 3);
            echo json_encode(Array("success" => "true", "pacote_id" => $parcela['id_pacote']));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function pacoteGetGroupMembers() {
        try {
            $data = filter_input_array(INPUT_POST, array(
                'data' => array('flags' => FILTER_FORCE_ARRAY, 'filters' => FILTER_VALIDATE_INT)
            ));
            $grupo_id = $data['data']['id_grupo'];
            $pacotecontroller = new pacoteController(new conn());
            $members = $pacotecontroller->getGroupMembers($grupo_id);
            echo json_encode(Array("success" => "true", "members" => $members));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

}

/**
 * Método para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_api($url) {
    $apicontroller = new apiController();
    $apicontroller->init($url);
}
