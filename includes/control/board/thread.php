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

        if ($thread['info']) {
            $info_array = json_decode($thread['info'], true);
            if ($info_array['checklist']) {
                $thread['checklist'] = $info_array['checklist'];
            }

            if ($info_array['ss']) {
                $thread['ss'] = $info_array['ss'];
            }
        }
        return $thread;
    }

    public function addThread($thread, $user) {
        if (!is_array($thread)) {
            throw new Exception("Thread enviada é inválida.");
        }

        if (!is_a($user, "user")) {
            throw new Exception("Usuário Inválido.");
        }
        $thread['board_id'] = $user->getSelectedBoard();
        if (!is_numeric($thread['board_id'])) {
            throw new Exception("A Board que você selecionou não está disponível.");
        }

        $pattern['title'] = "/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ0-9\&\;\\ ]*$/";
        if (trim($thread['title']) == "" || !preg_match($pattern['title'], $thread['title'])) {
            throw new Exception("Seu título contém caracteres inválidos ou está em branco. (" . $thread['title'] . ")");
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
            $thread['info']['attachments'] = $this->addThreadAttachments($thread['attachments']);
        }
        if ($thread['checklist']) {
            $thread['info']['checklist'] = $this->addThreadCheckList($thread['checklist']);
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
            $user_info = $user->getBasicInfo();
            $thread['info']['ss']['current_user'] = $user_info['nome'];
            $thread['info']['ss']['current_status'] = 0;
            $thread['info']['ss']['history'] = array(
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
            $thread['info']['ss']['last_update'] = $datetime->getTimestamp();
        }

        $fields = array("id_board", "id_usuario", "titulo", "post", "prioridade", "tipo");
        $values = array($thread['board_id'], $user->getId(), $thread['title'], $thread['post'], $thread['priority'], $thread['type']);
        if ($expiring_date) {
            $fields[] = "data_vencimento";
            $values[] = $expiring_date->format("Y-m-d h:i:s");
        }

        if ($thread['info'] && is_array($thread['info'])) {
            $fields[] = "info";
            $values[] = json_encode($thread['info']);
        }

        $this->conn->prepareinsert("thread", $values, $fields);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar a thread.");
        }

        $thread_id = $this->getUserLatestThread($user);
        return $thread_id;
    }

    private function getUserLatestThread($user) {
        $id = $user->getId();
        $this->conn->prepareselect("thread", "id", "id_usuario", $id, "", "", "", NULL, "", array("id", "DESC"), 1);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível carregar a thread do usuário.");
        }

        return $this->conn->fetch[0];
    }

    private function addThreadCheckList($checklist_json) {
        $checklist_array = json_decode($checklist_json, true);
        if (!is_array($checklist_array)) {
            throw new Exception("A checklist enviada não está especificada corretamente.");
        }

        $checklist = array();
        $checklist['title'] = trim(filter_var($checklist_array['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if ($checklist['title'] == "") {
            throw new Exception("O título da checklist está vazio ou contém caracteres inválidos.");
        }

        if (!is_array($checklist_array['items'])) {
            throw new Exception("A checklist precisa conter ao menos 01 item.");
        }

        foreach ($checklist_array['items'] as $index => $item) {
            $checklist['items'][$index]['title'] = trim(filter_var($item, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            if ($checklist['items'][$index]['title'] == "") {
                throw new Exception("Não é possível adicionar um item com o título vazio.");
            }
            $checklist['items'][$index]['id'] = $index;
            $checklist['items'][$index]['status'] = 0;
        }

        return $checklist;
    }

    public function updateThreadChecklist($thread_id, $checklist_items, $force_update = false) {
        if (!is_numeric($thread_id)) {
            throw new Exception("Você tentou atualizar a checklist de uma thread não identificada.");
        }

        $thread = $this->loadThread($thread_id);
        if (count($thread['checklist']['items']) != count($checklist_items)) {
            throw new Exception("O número de items na checklist não foi especificado corretamente.");
        }

        foreach ($thread['checklist']['items'] as $index => $item) {
            if ($item['id'] == $checklist_items[$index]['id']) {
                if ($checklist_items[$index]['status'] != $item['status'] && ($checklist_items[$index]['status'] === 0 || $checklist_items[$index]['status'] === 1)) {
                    $thread['checklist']['items'][$index]['status'] = $checklist_items[$index]['status'];
                }
            }
        }
        if ($force_update) {
            $this->updateThreadInfo($thread);
        }

        return $thread;
    }
    
    
    public function updateThreadStatus($thread_id, $status, $user, $force_update = false) {
        if (!is_numeric($thread_id)) {
            throw new Exception("Você tentou atualizar o status de uma thread não identificada.");
        }
        
        if($status < 0 || $status > 3) {
            throw new Exception("O status que você está tentando atribuir é inválido.");
        }
        
        $thread = $this->loadThread($thread_id);
        
        if(!$thread['ss']) {
            throw new Exception("A Thread não possui o sistema de status ligado.");
        }
        
        if(!$user->isAuth()) {
            throw new Exception("Usuário inválido para alterar o status.");
        }        
        
        $user_info = $user->getBasicInfo();
        
        
        if($status == $thread['ss']['current_status']) {
            return true;
        }
        
        $datetime = new DateTime();
        $old_log['status'] = $thread['ss']['current_status'];
        $old_log['user'] = $thread['ss']['current_user'];
        $old_log['time'] = $datetime->getTimestamp() - $thread['ss']['last_update'];
        
        switch($old_log['status']) {
            case 0:
                $thread['ss']['history']['idle']['time_elapsed'] += $old_log['time'];
                break;
            case 1:
                $thread['ss']['history']['development']['time_elapsed'] += $old_log['time'];
                break;
            case 2:
                $thread['ss']['history']['working']['time_elapsed'] += $old_log['time'];
                break;
            case 3:
                $thread['ss']['history']['completed']['time_elapsed'] += $old_log['time'];
                break;
        }
        
        $thread['ss']['current_status'] = $status;
        $thread['ss']['last_update'] = $datetime->getTimestamp();
        $thread['ss']['current_user'] = $user_info['nome'];
        
        
        if ($force_update) {
            $this->updateThreadInfo($thread);
        }

        return $thread;
    }

    private function updateThreadInfo($thread) {
        if (!is_numeric($thread['id'])) {
            throw new Exception("A Thread não pode ser atualizada pois sua identificação não está especificada.");
        }

        $new_info = Array();
        if ($thread['ss']) {
            $new_info['ss'] = $thread['ss'];
        }

        if ($thread['checklist']) {
            $new_info['checklist'] = $thread['checklist'];
        }

        if ($thread['attachments']) {
            $new_info['attachments'] = $thread['attachments'];
        }

        $new_info_json = json_encode($new_info);

        $this->conn->prepareupdate($new_info_json, "info", "thread", $thread['id'], "id", "STR");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível atualizar as informações da thread.");
        }
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
    
    
    public function archiveThread($thread_id) {
        if (!is_numeric($thread_id)) {
            throw new Exception("Você tentou atualizar o status de uma thread não identificada.");
        }        
        
        $thread = $this->loadThread($thread_id);
        
        if($thread['status'] == 1) {
            $new_status = 0;
        } else {
            $new_status = 1;
        }
        
        $this->conn->prepareupdate($new_status, "status", "thread", $thread['id'], "id", "INT");
        if(!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o status da thread.");
        }
    }

}
