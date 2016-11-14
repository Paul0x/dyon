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
 *  File: comments.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/control/usuario/users.php");
require_once("includes/lib/Twig/Autoloader.php");

class commentsController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    private function addComments($node_type, $node_id, $comment) {
        if (!$this->checkNode($node_id, $node_type)) {
            throw new Exception("O conteúdo no qual você está tentando adicionar o comentário não existe ou não foi encontrado.");
        }

        $comment = trim($comment);
        if ($comment == "") {
            throw new Exception("O comentário não pode estar vazio.");
        }

        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        $id = $this->conn->pegarMax("comentario");
        $this->conn->prepareinsert("comentario", array($node_type, $node_id, $user->getId(), $comment), array("node", "id_node", "id_usuario", "texto"), array("INT", "INT", "INT", "STR"));
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar o comentário.");
        }

        $comment_r = $this->getComment($id);



        return $comment_r;
    }

    private function getComment($comment_id) {
        if (!is_numeric($comment_id)) {
            throw new Exception("Identificador do ID está incorreto.");
        }

        $usercontroller = new userController();
        $user = $usercontroller->getUser();

        $fields = Array("a.id", "b.nome", "a.id_usuario", "a.texto", "a.data_criacao", "a.id_node", "b.image");
        $this->conn->prepareselect("comentario a", $fields, array("a.id"), array($comment_id), "same", "", array("INNER", "usuario b ON a.id_usuario = b.id"), PDO::FETCH_ASSOC);
        if (!$this->conn->executa()) {
            throw new Exception("Nenhum comentário encontrado.");
        }

        $comment = $this->conn->fetch;
        if ($comment['id_usuario'] == $user->getId() || $user->getPermission() == 10) {
            $comment['buttons'] = "true";
        }
        $datetime = new DateTime($comment['data_criacao']);
        $comment['data_criacao'] = $datetime->format("d/m/Y \à\s h:i");
        $comment['texto'] = nl2br($comment['texto']);

        if ($comment['image'] == "") {
            $comment['image'] = "noimage.jpg";
        }

        return $comment;
    }

    public function listComments($node_type, $node_id) {
        if (!$this->checkNode($node_id, $node_type)) {
            throw new Exception("Não foi possível listar os comentários do conteúdo selecionado.");
        }

        $usercontroller = new userController();
        $user = $usercontroller->getUser();

        $query = "SELECT a.id, b.nome, a.id_usuario, a.texto, a.data_criacao, a.id_node, b.image FROM comentario a INNER JOIN usuario b ON a.id_usuario = b.id WHERE  a.node = $node_type AND  a.id_node = $node_id ORDER BY a.data_criacao DESC LIMIT 0, 25";
        if (!$comments = $this->conn->freeQuery($query, true, true, PDO::FETCH_ASSOC)) {            
            throw new Exception("Nenhum comentário encontrado.");
        }

        foreach ($comments as $index => $comment) {
            $datetime = new DateTime($comment['data_criacao']);
            $comments[$index]['data_criacao'] = $datetime->format("d/m/Y \à\s h:i");
            $comments[$index]['texto'] = nl2br($comment['texto']);
            if ($comment['id_usuario'] == $user->getId() || $user->getPermission() == 10) {
                $comments[$index]['buttons'] = "true";
            }
            if ($comment['image'] == "") {
                $comments[$index]['image'] = "noimage.jpg";
            }
        }
        return $comments;
    }

    public function countComments($node_type, $node_id) {
        if (!$this->checkNode($node_id, $node_type)) {
            throw new Exception("Não foi possível contar os comentários do conteúdo selecionado.");
        }
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        $fields = Array("count(id)");
        $this->conn->prepareselect("comentario", $fields, array("node", "id_node"), array($node_type, $node_id), "same", "", "", PDO::FETCH_NUM);
        if (!$this->conn->executa()) {
            throw new Exception("Nenhum comentário encontrado.");
        }

        return $this->conn->fetch[0];
    }

    private function deleteComment($comment_id) {
        if (!is_numeric($comment_id)) {
            throw new Exception("Identificador do comentário inválido.");
        }
        $comment = $this->getComment($comment_id);
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        if ($user->getId() != $comment['id_usuario'] && $user->getPermission() != 10) {
            throw new Exception("Você não pode deletar um comentário em que você não é dono.");
        }

        $this->conn->preparedelete("comentario", "id", $comment_id);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível remover o comentário.");
        }
    }

    private function editComment($comment_id, $text) {
        if (!is_numeric($comment_id)) {
            throw new Exception("O identificador do comentário é inválido.");
        }
        $text = trim($text);
        if ($text == "") {
            throw new Exception("O comentário não pode estar vazio.");
        }
        $comment = $this->getComment($comment_id);
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        if ($user->getId() != $comment['id_usuario'] && $user->getPermission() != 10) {
            throw new Exception("Você não pode editar um comentário em que você não é dono.");
        }
        $this->conn->prepareupdate($text, 'texto', "comentario", $comment_id, "id", "STR");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível edit o comentário.");
        }
        $comment_r = $this->getComment($comment_id);
        return $comment_r;
    }

    private function checkNode($node_id, $node_type) {
        if(!is_numeric($node_id) || !is_numeric($node_type)) {
            return false;
        }
        
        if (!in_array($node_type, config::$nodes_ids) || !is_numeric($node_id)) {
            return false;
        }
        $node_table = config::$nodes[$node_type]['table'];
        $this->conn->prepareselect($node_table, "id", "id", $node_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            return false;
        }
        return true;
    }

    private function loadCommentsInterface() {
        try {
            $node_id = filter_input(INPUT_POST, "node_id", FILTER_VALIDATE_INT);
            $node = filter_input(INPUT_POST, "node", FILTER_VALIDATE_INT);
            $comments = $this->listComments($node, $node_id);
            echo json_encode(Array("success" => "true", "comments" => $comments));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function addCommentInterface() {
        try {
            $node_id = filter_input(INPUT_POST, "node_id", FILTER_VALIDATE_INT);
            $node = filter_input(INPUT_POST, "node", FILTER_VALIDATE_INT);
            $text = filter_input(INPUT_POST, "text", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $comment = $this->addComments($node, $node_id, $text);
            echo json_encode(Array("success" => "true", "comment" => $comment));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function deleteCommentInterface() {
        try {
            $comment_id = filter_input(INPUT_POST, "comment", FILTER_VALIDATE_INT);
            $this->deleteComment($comment_id);
            echo json_encode(Array("success" => "true"));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function editCommentInterface() {
        try {
            $comment_id = filter_input(INPUT_POST, "comment", FILTER_VALIDATE_INT);
            $text = filter_input(INPUT_POST, "text", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $comment = $this->editComment($comment_id, $text);
            echo json_encode(Array("success" => "true", "comment" => $comment));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    private function countCommentsInterface() {
        try {
            $node_id = filter_input(INPUT_POST, "node_id", FILTER_VALIDATE_INT);
            $node = filter_input(INPUT_POST, "node", FILTER_VALIDATE_INT);
            $count = $this->countComments($node, $node_id);
            echo json_encode(Array("success" => "true", "count" => $count));
        } catch (Exception $ex) {
            echo json_encode(Array("success" => "false", "error" => $ex->getMessage()));
        }
    }

    public function init($url) {
        if ($url['ajax'] == false) {
            echo json_encode(Array("success" => "false", "error" => "Tipo de requsição inválida."));
            return;
        }

        switch ($_POST['mode']) {
            case "loadComments":
                $this->loadCommentsInterface();
                break;
            case "sendComment":
                $this->addCommentInterface();
                break;
            case "deleteComment":
                $this->deleteCommentInterface();
                break;
            case "editComment":
                $this->editCommentInterface();
                break;
            case "countComments":
                $this->countCommentsInterface();
                break;
            default:
                echo json_encode(Array("success" => "false", "error" => "Tipo de requsição inválida."));
                break;
        }
    }

}

function init_module_comments($url) {
    $commentscontroller = new commentsController();
    $commentscontroller->init($url);
}
