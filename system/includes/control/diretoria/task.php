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
 *  File: task.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

class taskController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function loadBoardTasks($board_id = 1, $status = 1) {
        if (!is_numeric($board_id)) {
            throw new Exception("Identificador da diretoria é inválido.");
        }

        if (($status != 0 && $status != 1) || !is_numeric($status)) {
            throw new Exception("Status da tarefa indefinido.");
        }

        $fields = array("id", "id_diretoria", "id_usuario", "titulo", "descricao", "data_vencimento", "data_criacao", "prioridade");
        $this->conn->prepareselect("tarefa", $fields, array("id_diretoria", "status"), array($board_id, $status), "", "", "", PDO::FETCH_ASSOC, "all", array("prioridade", "DESC"));
        if (!$this->conn->executa()) {
            throw new Exception("Nenhuma tarefa encontrada nessa diretoria.");
        }

        $tasks = $this->conn->fetch;

        foreach ($tasks as $index => $task) {
            if (strlen($task['descricao']) > 100) {
                $tasks[$index]['descricao'] = substr($task['descricao'], 0, 100) . "...";
            }
            if ($task['data_vencimento']) {
                $datetime = new DateTime($task['data_vencimento']);
                $tasks[$index]['data_vencimento'] = $datetime->format("d/m/Y");
            }
            $datetime2 = new DateTime($task['data_criacao']);
            $tasks[$index]['data_criacao'] = $datetime2->format("d/m/Y");
        }

        return $tasks;
    }

    public function loadTask($task_id) {
        if (!is_numeric($task_id)) {
            throw new Exception("Identificador da tarefa é inválido.");
        }

        $fields = array("id", "id_diretoria", "id_usuario", "titulo", "descricao", "data_vencimento", "data_criacao", "prioridade", "status");
        $this->conn->prepareselect("tarefa", $fields, "id", $task_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Nenhuma tarefa encontrada.");
        }

        $task = $this->conn->fetch;
        $task['descricao_nl'] = $task['descricao'];
        $task['data_vencimento_uf'] = $task['data_vencimento'];
        if ($task['data_vencimento']) {
            $datetime = new DateTime($task['data_vencimento']);
            $task['data_vencimento'] = $datetime->format("d/m/Y");
        }
        $task['descricao'] = nl2br($task['descricao']);
        $datetime2 = new DateTime($task['data_criacao']);
        $task['data_criacao'] = $datetime2->format("d/m/Y");

        $usercontroller = new userController();
        $user = $usercontroller->getUser(5);
        if ($task['id_usuario'] == $user->getId() || $user->getPermission() == 10) {
            $task['is_owner'] = true;
        }
        try {
            $user_task = $usercontroller->getUser(0, false);
            $user_task->setId($task['id_usuario']);
            $user_task->setInfo();
            $userinfo = $user_task->getBasicInfo();
            $task['usuario'] = $userinfo['nome'];
        } catch (Exception $ex) {
            throw new Exception("Criador da tarefa não encontrado.");
        }
        return $task;
    }

    public function addTask($task) {
        if (!is_numeric($task['board_id'])) {
            throw new Exception("O identificador da board é inválido.");
        }

        $task['titulo'] = trim($task['titulo']);
        $task['desc'] = trim($task['desc']);

        if (!is_numeric($task['prioridade']) || ($task['prioridade'] < 0 && $task['prioridade'] > 3)) {
            $task['prioridade'] = 0;
        }

        if ($task['titulo'] == "") {
            throw new Exception("O título da tarefa não pode estar vazio.");
        }

        $fields = Array("id_usuario", "id_diretoria", "titulo", "descricao", "prioridade");
        $values = Array($task['user_id'], $task['board_id'], $task['titulo'], $task['desc'], $task['prioridade']);
        if (!is_null($task['vencimento']) && trim($task['vencimento']) != "") {
            $vencimento = explode("/", $task['vencimento']);
            if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {
                throw new Exception("A data de vencimento é inválida.");
            }
            $date = $vencimento[2] . "-" . $vencimento[1] . "-" . $vencimento[0] . " 23:59:59";
            $datetime = new DateTime($date);
            $task['vencimento'] = $datetime->format("Y-m-d h:i:s");
            $fields[] = "data_vencimento";
            $values[] = $task['vencimento'];
        }


        $this->conn->prepareinsert("tarefa", $values, $fields);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar a tarefa.");
        }
    }

    public function editTask($task) {
        $task_old = $this->loadTask($task['task_id']);
        $usercontroller = new userController();
        $user = $usercontroller->getUser(5);        
        if($task_old['id_usuario'] != $user->getId() && $user->getPermission() != 10) {
            throw new Exception("O usuário não tem permissão para editar a tarefa.");
        }        
        $task['titulo'] = trim($task['titulo']);
        $task['desc'] = trim($task['desc']);
        if (!is_numeric($task['prioridade']) || ($task['prioridade'] < 0 && $task['prioridade'] > 3)) {
            $task['prioridade'] = 0;
        }
        if ($task['titulo'] == "") {
            throw new Exception("O título da tarefa não pode estar vazio.");
        }
        $fields = Array("titulo", "descricao", "prioridade");
        $values = Array($task['titulo'], $task['desc'], $task['prioridade']);
        if (!is_null($task['vencimento']) && trim($task['vencimento']) != "") {
            $vencimento = explode("/", $task['vencimento']);
            if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {
                throw new Exception("A data de vencimento é inválida.");
            }
            $date = $vencimento[2] . "-" . $vencimento[1] . "-" . $vencimento[0] . " 23:59:59";
            $datetime = new DateTime($date);
            $task['vencimento'] = $datetime->format("Y-m-d h:i:s");
            $fields[] = "data_vencimento";
            $values[] = $task['vencimento'];
        }
        $this->conn->prepareupdate($values, $fields, "tarefa", $task_old['id'], "id");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível editar a tarefa.");
        }
        return $task_old['id'];
    }

    public function changeTaskStatus($task_id) {
        $task = $this->loadTask($task_id);
        if ($task['is_owner'] == false) {
            throw new Exception("O usuário não tem permissão para alterar o status da tarefa.");
        }

        if ($task['status'] == 1) {
            $new_status = 0;
        } else {
            $new_status = 1;
        }

        $this->conn->prepareupdate($new_status, "status", "tarefa", $task_id, "id", "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o status da tarefa.");
        }
    }

}
