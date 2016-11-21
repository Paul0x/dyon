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
        $thread['data_criacao'] = $datetime2->format("d/m/Y \à\s h:i");

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
        if (!is_array($thread)) {
            throw new Exception("Thread enviada é inválida.");
        }

        $pattern['title'] = "/^[A-Za-zÀ-ú0-9\\ ]*$/";
        if (trim($thread['title']) == "" || !preg_match($pattern['title'], $thread['title'])) {
            throw new Exception("Seu título contém caracteres inválidos ou está em branco.");
        }

        if (!$thread['post']) {
            throw new Exception("Sua postagem é inválida.");
        }

        if ($thread['type'] != 0 && $thread['type'] != 1) {
            $thread['type'] = 0;
        }

        if ($thread['priority'] != 0 && $thread['priority'] != 1 && $thread['priority'] != 2) {
            $thread['priority'] = 0;
        }

        if ($thread['attachments']) {
            $thread['attachments'] = $this->addThreadAttachments($thread['attachments']);
        }
        if ($thread['checklist']) {
            $thread['checklist'] = $this->addThreadCheckList($thread['checklist']);
        }

        $datetime = new Datetime();

        if ($thread['expiring_date']) {
            $expiring_date = DateTime::createFromFormat("d/m/Y", $thread['expiring_date']);
            if (!$expiring_date) {
                throw new Exception("A data de vencimento informada está em formato inválido.");
            }
            if ($datetime->getTimestamp() > $expiring_date->getTimeStamp()) {
                throw new Exception("A data de vencimento é inferior a data atual.");
            }
        }

        if ($thread['statussystem']) {
            $thread['ss']['current_status'] = 0;
            $thread['ss']['history'] = array(
                "idle" => array(
                    "time_elapsed" => 0
                ),
                "working" => array(
                    "time_elapsed" => 0
                ),
                "development" => array(
                    "time_elapsed" => 0
                ),
                "completed" => array(
                    "time_elapsed" => 0
                )
            );
            $thread['ss']['last_update'] = $datetime->getTimestamp();
        }
        
        print_r($thread);
    }

    private function addThreadAttachments($attachment_list) {
        $mime_array = array(
            "application/msword",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
            "application/vnd.ms-word.document.macroEnabled.12",
            "application/vnd.ms-word.template.macroEnabled.12",
            "application/vnd.ms-excel",
            "application/vnd.ms-excel",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
            "application/vnd.ms-excel.sheet.macroEnabled.12",
            "application/vnd.ms-excel.template.macroEnabled.12",
            "application/vnd.ms-excel.addin.macroEnabled.12",
            "application/vnd.ms-excel.sheet.binary.macroEnabled.12",
            "application/vnd.ms-powerpoint",
            "application/vnd.ms-powerpoint",
            "application/vnd.ms-powerpoint",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "application/vnd.openxmlformats-officedocument.presentationml.template",
            "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
            "application/vnd.ms-powerpoint.addin.macroEnabled.12",
            "application/vnd.ms-powerpoint.presentation.macroEnabled.12",
            "application/vnd.ms-powerpoint.template.macroEnabled.12",
            "application/vnd.ms-powerpoint.slideshow.macroEnabled.12",
            "application/pdf",
            "image/jpeg",
            "image/gif",
            "image/png"
        );

        if (!is_array($attachment_list)) {
            throw new Exception("A lista de anexos enviada não é válida.");
        }

        $attachment_array = array();

        $idx_counter = 0;
        foreach ($attachment_list as $index => $file) {
            if ($index != "attachment-file-" . $idx_counter) {
                throw new Exception("Não foi possível validar o envio dos anexos.");
            }
            
            if (!in_array($file['type'], $mime_array) || !is_file($file['tmp_name']) || strlen(basename($file['tmp_name'])) > 70) {
                throw new Exception("O arquivo " . $file['name'] . " está em formato não suportado pelo sistema.");
            }

            $file_extension = array_pop(explode(".", $file['name']));
            $file_name = uniqid("attach_", true);

            if (strlen($file_extension) != 3 && strlen($file_extension) != 4) {
                throw new Exception("A extensão do arquivo " . $file['name'] . " é inválida.");
            }

            if (!move_uploaded_file($file['tmp_name'], "./files/attachments/" . $file_name . "." . $file_extension)) {
                throw new Exception("Não foi possível fazer o upload dos anexos.");
            }

            $idx_counter++;
            $attachment_array[] = $file_name . "." . $file_extension;
        }

        return $attachment_array;
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
