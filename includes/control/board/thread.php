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
 *  File: thread.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

class threadController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function loadBoardThreads($board_id = 1, $status = 1) {
        if (!is_numeric($board_id)) {
            throw new Exception("Identificador da board é inválido.");
        }

        if (($status != 0 && $status != 1) || !is_numeric($status)) {
            throw new Exception("Status da thread indefinido.");
        }

        $query = "SELECT t.id, t.id_board, t.id_usuario, t.titulo, t.data_vencimento, t.data_criacao, t.prioridade, t.status, t.tipo, t.info, u.nome as 'nome_usuario' FROM thread t INNER JOIN usuario u ON t.id_usuario = u.id WHERE  id_board = $board_id AND  status = $status ORDER BY prioridade DESC, data_criacao DESC LIMIT 0, 25";
        $threads = $this->conn->freeQuery($query, true, true, PDO::FETCH_ASSOC);
        if (!$threads) {
            throw new Exception("Nenhuma thread encontrada nessa board.");
        }

        foreach ($threads as $index => $thread) {

            if ($thread['data_vencimento']) {
                $datetime = new DateTime($thread['data_vencimento']);
                $threads[$index]['data_vencimento'] = $datetime->format("d/m/Y");
            }
            $datetime2 = new DateTime($thread['data_criacao']);
            $threads[$index]['data_criacao'] = $datetime2->format("d/m/Y");
        }

        return $threads;
    }

    public function loadThread($thread_id) {
        if (!is_numeric($thread_id)) {
            throw new Exception("Identificador da thread é inválido.");
        }

        $fields = array("id", "id_board", "id_usuario", "titulo", "post", "data_vencimento", "data_criacao", "prioridade", "status", "info", "tipo");
        $this->conn->prepareselect("thread", $fields, "id", $thread_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Nenhuma thread encontrada.");
        }

        $thread = $this->conn->fetch;
        $thread['post_nl'] = $thread['post'];
        $thread['data_vencimento_uf'] = $thread['data_vencimento'];
        if ($thread['data_vencimento']) {
            $datetime = new DateTime($thread['data_vencimento']);
            $thread['data_vencimento'] = $datetime->format("d/m/Y");
        }
        $thread['post'] = nl2br($thread['post']);
        $datetime2 = new DateTime($thread['data_criacao']);
        $thread['data_criacao'] = $datetime->format("d/m/Y \à\s h:i");

        $usercontroller = new userController();
        $user = $usercontroller->getUser(5);
        if ($thread['id_usuario'] == $user->getId() || $user->getPermission() == 10) {
            $thread['is_owner'] = true;
        }
        try {
            $user_thread = $usercontroller->getUser(0, false);
            $user_thread->setId($thread['id_usuario']);
            $user_thread->setInfo();
            $thread['user'] = $user_thread->getBasicInfo();
        } catch (Exception $ex) {
            throw new Exception("Criador da thread não encontrado.");
        }
        return $thread;
    }

    public function addThread($thread) {
        if (!is_numeric($thread['board_id'])) {
            throw new Exception("O identificador da board é inválido.");
        }

        $thread['titulo'] = trim($thread['titulo']);
        $thread['desc'] = trim($thread['desc']);

        if (!is_numeric($thread['prioridade']) || ($thread['prioridade'] < 0 && $thread['prioridade'] > 3)) {
            $thread['prioridade'] = 0;
        }

        if ($thread['titulo'] == "") {
            throw new Exception("O título da thread não pode estar vazio.");
        }

        $fields = Array("id_usuario", "id_board", "titulo", "descricao", "prioridade");
        $values = Array($thread['user_id'], $thread['board_id'], $thread['titulo'], $thread['desc'], $thread['prioridade']);
        if (!is_null($thread['vencimento']) && trim($thread['vencimento']) != "") {
            $vencimento = explode("/", $thread['vencimento']);
            if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {
                throw new Exception("A data de vencimento é inválida.");
            }
            $date = $vencimento[2] . "-" . $vencimento[1] . "-" . $vencimento[0] . " 23:59:59";
            $datetime = new DateTime($date);
            $thread['vencimento'] = $datetime->format("Y-m-d h:i:s");
            $fields[] = "data_vencimento";
            $values[] = $thread['vencimento'];
        }


        $this->conn->prepareinsert("thread", $values, $fields);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar a thread.");
        }
    }

    public function editThread($thread) {
        $thread_old = $this->loadThread($thread['thread_id']);
        $usercontroller = new userController();
        $user = $usercontroller->getUser(5);
        if ($thread_old['id_usuario'] != $user->getId() && $user->getPermission() != 10) {
            throw new Exception("O usuário não tem permissão para editar a thread.");
        }
        $thread['titulo'] = trim($thread['titulo']);
        $thread['desc'] = trim($thread['desc']);
        if (!is_numeric($thread['prioridade']) || ($thread['prioridade'] < 0 && $thread['prioridade'] > 3)) {
            $thread['prioridade'] = 0;
        }
        if ($thread['titulo'] == "") {
            throw new Exception("O título da thread não pode estar vazio.");
        }
        $fields = Array("titulo", "descricao", "prioridade");
        $values = Array($thread['titulo'], $thread['desc'], $thread['prioridade']);
        if (!is_null($thread['vencimento']) && trim($thread['vencimento']) != "") {
            $vencimento = explode("/", $thread['vencimento']);
            if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {
                throw new Exception("A data de vencimento é inválida.");
            }
            $date = $vencimento[2] . "-" . $vencimento[1] . "-" . $vencimento[0] . " 23:59:59";
            $datetime = new DateTime($date);
            $thread['vencimento'] = $datetime->format("Y-m-d h:i:s");
            $fields[] = "data_vencimento";
            $values[] = $thread['vencimento'];
        }
        $this->conn->prepareupdate($values, $fields, "thread", $thread_old['id'], "id");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível editar a thread.");
        }
        return $thread_old['id'];
    }

    public function getThreadReplys($thread_id) {
        if (!is_numeric($thread_id)) {
            throw new Exception("O identificador da thread é inválido.");
        }
        try {
            $commentcontroller = new commentsController();
            $replys = $commentcontroller->listComments(6, $thread_id);
        } catch (Exception $ex) {
            return false;
        }
        return $replys;
    }

}
