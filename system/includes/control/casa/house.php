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
 *  File: hosue.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once(config::$syspath."includes/sql/sqlcon.php");
require_once(config::$syspath."includes/sql/control.php");
require_once(config::$syspath."includes/control/evento/events.php");
require_once(config::$syspath."includes/control/evento/pacotes.php");
require_once(config::$syspath."includes/control/usuario/users.php");
require_once(config::$syspath."includes/control/controle/cliente_overview.php");
require_once(config::$syspath."includes/lib/Twig/Autoloader.php");

class houseController {

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
            if ($url['ajax']) {
                switch ($_POST['mode']) {
                    case "add_house_form":
                        $this->addHouse();
                        break;
                    case "load_house":
                        $this->loadHouse();
                        break;
                    case "load_room":
                        $this->loadRoom();
                        break;
                    case "remove_member":
                        $this->removeMember();
                        break;
                    case "add_member_search_group":
                        $this->searchAddMember("group");
                        break;
                    case "add_member_search_people":
                        $this->searchAddMember("people");
                        break;
                    case "submit_add_members":
                        $this->addMembers();
                        break;
                }
            } else {
                $this->interfaceMainHouses($url);
            }
        }
    }

    private function interfaceMainHouses($url) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $eventcontroller = new eventController();
            $events_select = $eventcontroller->listEvents(array(), true);
            $selected_event = $user->getSelectedEvent();
            try {
                $house_list = $this->listHouses($user, $selected_event);
            } catch (Exception $error) {
                $no_house = true;
            }
            echo $this->twig->render("casa/main_house.twig", Array("no_house" => $no_house, "house_list" => $house_list, "user" => $user->getBasicInfo(), "events_select" => $events_select, "selected_event" => $selected_event, "config" => config::$html_preload));
        } catch (Exception $error) {
            echo $this->twig->render("casa/main_house.twig", Array("house_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

    private function listHouses($user, $selected_event) {
        try {
            if (!is_numeric($selected_event)) {
                $selected_event = $user->getSelectedEvent();
            }

            $fields = array("id", "id_evento", "nome", "numero_vagas");
            $this->conn->prepareselect("casa", $fields, "id_evento", $selected_event, "", "", "", PDO::FETCH_ASSOC, "all", array("nome", "ASC"));
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível encontrar nenhuma casa para esse evento.");
            }

            return $this->conn->fetch;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function loadHouse() {
        try {
            $id_house = filter_input(INPUT_POST, "house", FILTER_VALIDATE_INT);
            $render = filter_input(INPUT_POST, "render", FILTER_VALIDATE_BOOLEAN);

            if (!is_numeric($id_house) || !is_bool($render)) {
                throw new Exception("Variáveis da casa inválidas.");
            }

            $fields = array("id", "id_evento", "nome", "endereco", "valor_pessoa", "numero_vagas", "anexo_contrato", "mapa");
            $this->conn->prepareselect("casa", $fields, "id", $id_house, "", "", "", PDO::FETCH_ASSOC);
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível localizar a casa.");
            }
            $house = $this->conn->fetch;

            $house['valor_total'] = number_format($house['valor_pessoa'] * $house['numero_vagas'], 2, ",", ".");
            $house['valor_pessoa'] = number_format($house['valor_pessoa'], 2, ",", ".");

            $house['rooms'] = $this->loadRooms($house['id']);

            if ($render == true) {
                $return_house = $this->twig->render("casa/load_house.twig", Array("house" => $house, "config" => config::$html_preload));
            } else {
                $return_house = $house;
            }
            echo json_encode(array("success" => "true", "return" => $return_house));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadRooms($id_house) {

        if (!is_numeric($id_house)) {
            throw new Exception("Não foi possível identificar os quartos da casa.");
        }

        $fields = array("id", "id_casa", "numero", "numero_vagas", "suite");
        $this->conn->prepareselect("quarto q", $fields, "id_casa", $id_house, "", "", "", PDO::FETCH_ASSOC, "all");
        if (!$this->conn->executa()) {
            throw new Exception("Não existem quartos na casa selecionada.");
        }
        $rooms = $this->conn->fetch;

        foreach ($rooms as $index => $room) {
            $rooms[$index]['ocupados'] = $this->countRoomMembers($room['id']);
        }


        return $rooms;
    }

    private function loadRoom($render = true, $id_room = false) {
        try {
            if ($id_room == false) {
                $id_room = filter_input(INPUT_POST, "id_room", FILTER_VALIDATE_INT);
            }
            if (!is_numeric($id_room)) {
                throw new Exception("Não foi possível identificar o quarto selecionado.");
            }

            $fields = array("id", "id_casa", "numero", "numero_vagas", "suite");
            $this->conn->prepareselect("quarto", $fields, "id", $id_room, "", "", "", PDO::FETCH_ASSOC);
            if (!$this->conn->executa()) {
                throw new Exception("Quarto selecionado não encontrado");
            }

            $room = $this->conn->fetch;
            $room['ocupados'] = $this->countRoomMembers($room['id']);

            $fields = array("nome", "numero_vagas");
            $this->conn->prepareselect("casa", $fields, "id", $room['id_casa'], "", "", "", PDO::FETCH_ASSOC);
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível encontrar a casa associada ao quarto.");
            }

            $room['casa'] = $this->conn->fetch;

            if ($room['ocupados'] != 0) {
                $room['members'] = $this->loadRoomMembers($room['id']);
            }


            if ($render == true) {
                echo json_encode(array("success" => "true", "room" => $room));
            } else {
                return $room;
            }
        } catch (Exception $ex) {
            if ($render == true) {
                echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
            } else {
                return false;
            }
        }
    }

    private function countRoomMembers($id_room) {
        if (!is_numeric($id_room)) {
            throw new Exception("O identificador do quarto está incorreto.");
        }

        $this->conn->prepareselect("pacote", "count(id)", "id_quarto", $id_room);
        if (!$this->conn->executa()) {
            return 0;
        } else {
            return $this->conn->fetch[0];
        }
    }

    private function loadRoomMembers($id_room) {
        if (!is_numeric($id_room)) {
            throw new Exception("O identificador do quarto está incorreto.");
        }

        $fields = array("p.id", "p.id_usuario", "p.id_grupo", "g.nome as 'nome_grupo'", "u.nome as 'nome_usuario', g.codigo_acesso, p.status");
        $this->conn->prepareselect("pacote p", $fields, "id_quarto", $id_room, "", "", array("INNER", "usuario u ON p.id_usuario = u.id INNER JOIN grupo g ON p.id_grupo = g.id"), PDO::FETCH_ASSOC, "all");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi encontrado nenhum membro na casa.");
        }

        return $this->conn->fetch;
    }

    private function addHouse() {
        try {
            $user = $this->usercontroller->getUser(DYON_USER_ADMIN);
            $house = filter_input_array(INPUT_POST, array(
                "valor_pessoa" => array('filter' => FILTER_VALIDATE_FLOAT),
                "quantidade_vagas" => array('filter' => FILTER_VALIDATE_INT),
                "quartos" => array('flags' => FILTER_REQUIRE_ARRAY)
                    )
            );

            $house['nome'] = filter_input(INPUT_POST, "nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $house['endereco'] = filter_input(INPUT_POST, "endereco", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if ($house['nome'] == "" || $house['endereco'] == "") {
                throw new Exception("O nome e o endereço são obrigatórios");
            }

            if (!is_numeric($house['valor_pessoa'])) {
                throw new Exception("O valor por pessoa deve ser numérico.");
            }


            if (!is_numeric($house['valor_pessoa'])) {
                throw new Exception("A quantidade de vagas deve ser numérica.");
            }

            foreach ($house['quartos'] as $index => $quarto) {
                if (!is_numeric($quarto['numero']) || !is_numeric($quarto['vagas'])) {
                    throw new Exception("O número do quarto e a quantidade de vagas devem ter valores numéricos.");
                }

                if (( $quarto['tipo'] != 0 && $quarto['tipo'] != 1 ) || !is_numeric($quarto['tipo'])) {
                    throw new Exception("O tipo do quarto é inválido.");
                }
            }

            $conn = new conn();
            $fields = Array("nome", "endereco", "numero_vagas", "valor_pessoa", "id_evento");
            $values = Array($house['nome'], $house['endereco'], $house['quantidade_vagas'], $house['valor_pessoa'], $user->getSelectedEvent());
            $bind = Array("STR", "STR", "INT", "STR", "INT");
            $this->conn->prepareinsert("casa", $values, $fields, $bind);
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível adicionar a casa.");
            }

            $house['id'] = $conn->pegarMax("casa") - 1;

            foreach ($house['quartos'] as $index => $quarto) {

                $fields = Array("id_casa", "numero", "numero_vagas", "suite");
                $values = Array($house['id'], $quarto['numero'], $quarto['vagas']);
                if ($quarto['tipo'] == 1) {
                    $values[] = 's';
                } else {
                    $values[] = 'n';
                }
                $this->conn->prepareinsert("quarto", $values, $fields);
                if (!$this->conn->executa()) {
                    throw new Exception("Não foi possível adicionar os quartos da casa, refaça a operação ou edite a casa já criada.");
                }
            }
            echo json_encode(array("success" => "true", "house" => $house));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function searchAddMember($type) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $selected_event = $user->getSelectedEvent();
            $query = filter_input(INPUT_POST, "query", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            switch ($type) {
                case "group":
                    $grupocontroller = new grupoController($this->conn);
                    $group_result = $grupocontroller->getGroupByCod($query);
                    $group = $grupocontroller->loadGroupInfo($group_result['id'], array(2, 3));
                    $results = $group['members'];
                    break;
                case "people":
                    $pacotecontroller = new pacoteController($this->conn);
                    $filters = array();
                    $filters['field']['label'] = "nome";
                    $filters['field']['value'] = $query;
                    $filters['status'] = array(2, 3);
                    $filters['id_evento'] = $selected_event;
                    $results = $pacotecontroller->listPacotes($filters);
                    break;
            }

            echo json_encode(array("success" => "true", "results" => $results));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function removeMember() {
        try {
            $id_pacote = filter_input(INPUT_POST, "member", FILTER_VALIDATE_INT);

            if (!is_numeric($id_pacote)) {
                throw new Exception("Não foi possível identificar o quarto selecionado.");
            }

            if ($id_pacote == 0) {
                throw new Exception("Identificador do pacote inválido.");
            }

            try {
                $query = "UPDATE pacote SET id_quarto = NULL WHERE id = $id_pacote";
                $this->conn->freeQuery($query, false, false);
            } catch (PDOException $error) {
                throw new Exception("Não foi posível executar a query.");
            }

            echo json_encode(array("success" => "true", "room" => $room));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addMembers() {
        try {
            $vars = filter_input_array(INPUT_POST, array(
                "room" => array('filter' => FILTER_VALIDATE_INT),
                "pacotes" => array('flags' => FILTER_REQUIRE_ARRAY, 'filter' => FILTER_VALIDATE_INT)
                    )
            );

            $room = $this->loadRoom(false, $vars['room']);
            if (!$room) {
                throw new Exception("Identificador do quarto inválido.");
            }            

            $pacotecontroller = new pacoteController($this->conn);
            if (!is_array($vars['pacotes'])) {
                throw new Exception("Lista de pacotes inválida.");
            }
            
            $numero_pacotes = count($vars['pacotes']);
            
            if($numero_pacotes+$room['ocupados'] > $room['numero_vagas']) {
                throw new Exception("Você está excedendo o número de vagas no quarto.");
            }

            foreach ($vars['pacotes'] as $index => $id_pacote) {
                $pacote = $pacotecontroller->loadPacoteById($id_pacote);
                if (is_numeric($pacote['id_quarto']) || $pacote['id_quarto'] !== NULL) {
                    throw new Exception("O pacote do usuário " . $pacote['nome_usuario'] . " já está em um quarto.");
                }
            }

            foreach ($vars['pacotes'] as $index => $id_pacote) {
                $this->conn->prepareupdate($vars['room'], "id_quarto", "pacote", $id_pacote, "id", "INT");
                if (!$this->conn->executa()) {
                    throw new Exception("Não foi possível adicionar o pacote dentro do quarto selecionado.");
                }
            }

            echo json_encode(array("success" => "true", "house" => $room['id_casa']));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

}

/**
 * Método para inicializar a classe de controle, chamada pelo sistema.
 * @param Array $url
 */
function init_module_house($url) {
    $eventcontroller = new houseController();
    $eventcontroller->init($url);
}
