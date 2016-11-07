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
require_once("includes/control/diretoria/task.php");

class boardController {

    /**
     * ConstrÃ³i o objeto e inicializa a conexÃ£o com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }
    
    private function loadBoardInfo($board_id) {
        if(!is_numeric($board_id)) {
            throw new Exception("O identificador da diretoria é inválido.");
        }        
        $fields = array("id","nome","acesso_minimo","id_usuario","id_instancia");
        $this->conn->prepareselect("diretoria",$fields, "id", $board_id);
        if(!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Não foi possível encontrar a board.");
        }        
        $board = $this->conn->fetch;
        return $board;        
    }
    
    private function isBoardMember($user_id, $board_id) {
        if(!is_numeric($user_id) || !is_numeric($board_id)) {
            throw new Exception("Os identificadores precisam ser numéricos.");
        }
        
        $this->conn->prepareselect("diretoria_usuarios", "id_usuario", array("id_usuario","id_diretoria"), array($user_id,$board_id));
        if(!$this->conn->executa() || $this->conn->rowcount != 1) {
            return false;
        }
        
        return true;        
    }
    
    private function loadBoardBoxes() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $instance = $user->getUserInstance();
            
            $this->conn->prepareselect("diretoria",array("id","nome"),"id_instancia",$instance['id'],"", "", "", PDO::FETCH_ASSOC, "all");
            if(!$this->conn->executa()) {
                throw new Exception("Nenhuma diretoria encontrada.");
            }
            $diretorias = $this->conn->fetch;
            echo json_encode(array("success" => "true", "diretorias" => $diretorias));
        } catch(Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }
    
    private function loadBoard() {
        try {
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $board_id = $user->getSelectedBoard();    
            $board = $this->loadBoardInfo($board_id);
            if($user->getId() == $board['id_usuario']  || $user->getPermission() == 10) {
                $board['controllers_admin'] = true;
            }
            if($this->isBoardMember($user->getId(), $board_id) || $user->getPermission() == 10) {
                $board['is_member'] = true;
            }
            echo json_encode(array("success" => "true", "board" => $board));            
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }
    
    private function updateUserBoard() {
        try {
            $board_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $user->setSelectedBoard($board_id);
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }
    }
    
    private function loadBoardTasks() {
        try {
            $board_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $taskcontroller = new taskController($this->conn);
            $tasks = $taskcontroller->loadBoardTasks($board_id);
            echo json_encode(array("success" => "true", "tasks" => $tasks));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }        
    }
    
    private function loadBoardMembers() {
        try {
            $board_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            
            $this->conn->prepareselect("diretoria_usuarios a", array("a.id_usuario as 'id'","b.nome"), "id_diretoria", $board_id, "same", "", array("INNER","usuario b ON b.id = a.id_usuario"), PDO::FETCH_ASSOC, "all");
            if(!$this->conn->executa() || $this->conn->rowcount == 0) {
                throw new Exception("Nenhum membro encontrado.");
            }
            
            $users = $this->conn->fetch;            
            echo json_encode(array("success" => "true", "users" => $users));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }        
    }
    
    private function loadTask() {
        try {
            $task_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $taskcontroller = new taskController($this->conn);
            $task = $taskcontroller->loadTask($task_id);
            echo json_encode(array("success" => "true", "task" => $task));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }        
    }    
    
    private function addTask() {
        try {
            $task['board_id'] = filter_input(INPUT_POST, "board", FILTER_VALIDATE_INT);
            $task['titulo'] = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['vencimento'] = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['desc'] = filter_input(INPUT_POST, "desc", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['prioridade'] = filter_input(INPUT_POST, "prioridade", FILTER_VALIDATE_INT);
            $usercontroller = new userController();
            $user = $usercontroller->getUser(5);
            $task['user_id'] = $user->getId();
            
            if(!$this->isBoardMember($user->getId(), $task['board_id']) && $user->getPermission() != 10) {
                throw new Exception("O usuÃ¡rio nÃ£o tem permissÃ£o para adicionar tarefas na board.");
            }
            
            $taskcontroller = new taskController($this->conn);
            $taskcontroller->addTask($task);            
            echo json_encode(array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }        
    }  
    
    private function editTask() {
        try {
            $task['task_id'] = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $task['titulo'] = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['vencimento'] = filter_input(INPUT_POST, "vencimento", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['desc'] = filter_input(INPUT_POST, "desc", FILTER_SANITIZE_SPECIAL_CHARS);
            $task['prioridade'] = filter_input(INPUT_POST, "prioridade", FILTER_VALIDATE_INT);                        
            $taskcontroller = new taskController($this->conn);
            $id = $taskcontroller->editTask($task);            
            echo json_encode(array("success" => "true", "task" => $id));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));
        }        
    }  
    
    private function changeTaskStatus() {
        try {
            $task_id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
            $taskcontroller = new taskController($this->conn);
            $taskcontroller->changeTaskStatus($task_id);
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
            $user = $this->usercontroller->getUser(5);
            if ($url['ajax'] == false) {
                echo $this->twig->render("diretoria/main_board.twig", Array("config" => config::$html_preload, "login_error_flag" => $login_error_flag, "user" => $user->getBasicInfo()));
            } else {
                switch($_POST['mode']) {
                    case "load_boards_boxes":
                        $this->loadBoardBoxes();
                        break;
                    case "load_user_board":
                        $this->loadBoard();
                        break;
                    case "update_user_board":
                        $this->updateUserBoard();
                        break;
                    case "load_board_tasks":
                        $this->loadBoardTasks();
                        break;
                    case "load_board_members":
                        $this->loadBoardMembers();
                        break;
                    case "load_task":
                        $this->loadTask();
                        break;
                    case "board_add_task":
                        $this->addTask();
                        break;
                    case "change_task_status":
                        $this->changeTaskStatus();
                        break;
                    case "board_edit_task":
                        $this->editTask();
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
