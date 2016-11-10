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
 *  File: board.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/control/usuario/users.php");
require_once("includes/lib/Twig/Autoloader.php");
require_once("includes/control/board/thread.php");

class boardController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    private function createBoard($name = false) {
        try {
            if (!$name) {
                $name = filter_input(INPUT_POST, "nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
            if (trim($name) == "") {
                throw new Exception("Nome da board inválido.");
            }
            $instance = $this->user->getUserInstance();

            $this->conn->prepareinsert("board", array($name, $instance['id']), array("nome", "id_instancia"));
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível criar a board.");
            }

            $board_id = $this->conn->pegarMax("board") - 1;
            $this->updateUserBoard($board_id, true);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }
    
    private function renameBoard($name = false, $board_id = false) {
        try {
            if (!$name) {
                $name = filter_input(INPUT_POST, "nome", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
            if (!$board_id) {
                $board_id = filter_input(INPUT_POST, "board_id", FILTER_VALIDATE_INT);
            }
            if (trim($name) == "") {
                throw new Exception("Nome da board inválido.");
            }
            if (!is_numeric($board_id)) {
                throw new Exception("Identificador da board inválido.");
            }
            $instance = $this->user->getUserInstance();
            $this->conn->prepareupdate($name, "nome", "board", array($board_id,$instance['id']), array("id","id_instancia"), "STR");
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível criar a board.");
            }
            $this->updateUserBoard($board_id, true);
            echo json_encode(array("success" => "true", "nome" => $name));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadBoardInfo($board_id) {
        if (!is_numeric($board_id)) {
            throw new Exception("O identificador da board � inv�lido.");
        }
        $fields = array("id", "nome", "id_instancia");
        $this->conn->prepareselect("board", $fields, "id", $board_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("N�o foi poss�vel encontrar a board.");
        }
        $board = $this->conn->fetch;
        return $board;
    }

    private function isBoardMember($user_id, $board_id) {
        if (!is_numeric($user_id) || !is_numeric($board_id)) {
            throw new Exception("Os identificadores precisam ser num�ricos.");
        }

        $this->conn->prepareselect("board_usuarios", "id_usuario", array("id_usuario", "id_board"), array($user_id, $board_id));
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            return false;
        }

        return true;
    }

    private function loadInstanceBoards() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $instance = $user->getUserInstance();

            $this->conn->prepareselect("board", array("id", "nome"), "id_instancia", $instance['id'], "", "", "", PDO::FETCH_ASSOC, "all");
            if (!$this->conn->executa()) {
                throw new Exception("Nenhuma board encontrada.");
            }
            $boards = $this->conn->fetch;
            echo json_encode(array("success" => "true", "boards" => $boards));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadUserBoard() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $board_id = $user->getSelectedBoard();
            $board = $this->loadBoardInfo($board_id);
            if ($user->getId() == $board['id_usuario'] || $user->getPermission() == 10) {
                $board['controllers_admin'] = true;
            }
            if ($this->isBoardMember($user->getId(), $board_id) || $user->getPermission() == 10) {
                $board['is_member'] = true;
            }
            try {
                $board['threads'] = $this->loadBoardThreads($board['id']);
            } catch (Exception $ex) {
                $board['no_thread'] = true;
            }
            Twig_Autoloader::register();
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
            $this->twig = new Twig_Environment($this->twig_loader);
            $html = $this->twig->render("board/board_load.twig", Array("config" => config::$html_preload, "board" => $board, "user" => $user->getBasicInfo()));

            echo json_encode(array("success" => "true", "html" => $html, "board" => $board));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function updateUserBoard($board_id = false, $noecho = false) {
        try {
            if (!$board_id) {
                $board_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            }
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $user->setSelectedBoard($board_id);
            if (!$noecho) {
                echo json_encode(array("success" => "true"));
            } else {
                return true;
            }
        } catch (Exception $ex) {
            if (!$noecho) {
                echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
            } else {
                return true;
            }
        }
    }

    private function loadBoardThreads($board_id, $status = 1) {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $threadcontroller = new threadController($this->conn);
            $threads = $threadcontroller->loadBoardThreads($board_id, $status);
            return $threads;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function loadBoardMembers() {
        try {
            $board_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);

            $this->conn->prepareselect("board_usuarios a", array("a.id_usuario as 'id'", "b.nome"), "id_board", $board_id, "same", "", array("INNER", "usuario b ON b.id = a.id_usuario"), PDO::FETCH_ASSOC, "all");
            if (!$this->conn->executa() || $this->conn->rowcount == 0) {
                throw new Exception("Nenhum membro encontrado.");
            }

            $users = $this->conn->fetch;
            echo json_encode(array("success" => "true", "users" => $users));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function loadThread() {
        try {
            $thread_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $threadcontroller = new threadController($this->conn);
            $thread = $threadcontroller->loadThread($thread_id);
            echo json_encode(array("success" => "true", "thread" => $thread));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addThread() {
        try {
            $thread['board_id'] = filter_input(INPUT_POST, "board", FILTER_VALIDATE_INT);
            $thread['titulo'] = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['vencimento'] = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['desc'] = filter_input(INPUT_POST, "desc", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['prioridade'] = filter_input(INPUT_POST, "prioridade", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $thread['user_id'] = $user->getId();

            if (!$this->isBoardMember($user->getId(), $thread['board_id']) && $user->getPermission() != 10) {
                throw new Exception("O usuário não tem permissão para adicionar tarefas na board.");
            }

            $threadcontroller = new threadController($this->conn);
            $threadcontroller->addThread($thread);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editThread() {
        try {
            $thread['thread_id'] = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $thread['titulo'] = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['vencimento'] = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['desc'] = filter_input(INPUT_POST, "desc", FILTER_SANITIZE_SPECIAL_CHARS);
            $thread['prioridade'] = filter_input(INPUT_POST, "prioridade", FILTER_VALIDATE_INT);
            $threadcontroller = new threadController($this->conn);
            $id = $threadcontroller->editThread($thread);
            echo json_encode(array("success" => "true", "thread" => $id));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function changeThreadStatus() {
        try {
            $thread_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $threadcontroller = new threadController($this->conn);
            $threadcontroller->changeThreadStatus($thread_id);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    public function init($url) {
        try {
            Twig_Autoloader::register();
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
            $this->twig = new Twig_Environment($this->twig_loader);
            $this->usercontroller = new userController();
            $this->user = $this->usercontroller->getUser();
            if ($url['ajax'] == false) {
                echo $this->twig->render("board/main_board.twig", Array("config" => config::$html_preload, "login_error_flag" => $login_error_flag, "user" => $this->user->getBasicInfo()));
            } else {
                switch ($_POST['mode']) {
                    case "load_boards_boxes":
                        $this->loadInstanceBoards();
                        break;
                    case "load_user_board":
                        $this->loadUserBoard();
                        break;
                    case "update_user_board":
                        $this->updateUserBoard();
                        break;
                    case "load_board_members":
                        $this->loadBoardMembers();
                        break;
                    case "load_thread":
                        $this->loadThread();
                        break;
                    case "board_add_thread":
                        $this->addThread();
                        break;
                    case "change_thread_status":
                        $this->changeThreadStatus();
                        break;
                    case "board_edit_thread":
                        $this->editThread();
                        break;
                    case "create_new_board":
                        $this->createBoard();
                        break;
                    case "rename_board":
                        $this->renameBoard();
                        break;
                }
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

}

function init_module_board($url) {
    $boardcontroller = new boardController();
    $boardcontroller->init($url);
}
