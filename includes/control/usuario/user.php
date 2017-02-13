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
 *  File: user.php
 *  Type: Controller
 *  =====================================================================
 *  Um objeto do tipo "user" representa um usuário logado no sistema, a classe 
 *  deve ser assosciada a todo sessão logada e serve para verificar as permissões 
 *  de acesso e informações únicas do usuário.
 *  =====================================================================
 *  Lista de erros da classe:
 *  [001] - Erro ao executar ação.
 *  [201] - Usuário inexistente.
 *  [202] - Falha na comunicação do usuário. (DB ERROR)
 *  [203] - Usuário não está logado.
 *  [101] - ID não é válido.
 *  [102] - Aarray inválido.
 *  [103] - Senha inválida.
 *  [104] - Nome inválido.
 *  [105] - Email inválido.
 *  [106] - Senha pequena.
 * 
 */

/* Bibliotecas necessárias para funcionamento da classe */
require_once(config::$syspath . "includes/sql/sqlcon.php");
require_once(config::$syspath . "includes/lib/imagemanager.php");
require_once(config::$syspath . "includes/control/instancia/instances.php");

define("DYON_USER_ADMIN", 5);
define("DYON_USER_CLIENTE", 1);
define("DYON_USER_MODERATOR", 2);
define("DYON_USER_ROOT", 10);

class user {

    private $id; // id do usuário
    private $senha_hash; // hash do password do usuário
    private $email; // email do usuário
    private $rg; // identificação do usuário
    private $sexo; // sexo do usuário
    private $nome; // nome do usuário
    private $instance; // instância atual do usuário
    private $instances; // todas as instâncias do usuário
    private $data_nascimento;
    protected $conn; // AUXILIAR: conexão com o banco de dados
    private $is_auth = false; // flag de autenticação do usuário
    private $date_create; // data de criação da conta do usuário
    private $user_cliente; // cliente relacionado com a conta do usuário
    private $admin_info; // Informações relacionadas ao administrador
    //Pattern de verifição do email.
    protected $email_pattern = "/.+@.+\..+/i";

    /**
     * __construct
     * @param $conn CONEXÃO DE DADOS
     * 
     * Função para inicializar a classe e atribuir algumas informações.
     */
    protected function __construct($conn) {
        $this->conn = $conn;
        $this->date_create = time();
    }

    /**
     * getCreationDate()
     * @return UNIX TIME
     * 
     * Retorna a data de criação do objeto em questão.
     */
    public function getLoginDate() {
        return $this->date_create;
    }

    /**
     * setConn()
     * @param sqlcon $conn
     * 
     * Altera a conexão de banco de dados do usuário.
     */
    public function setConn($conn) {
        $this->conn = $conn;
    }

    /**
     * setInfo()
     * @throws Exception [202]
     * 
     * Carrega as informações do usuário a partir da base de dados.
     */
    public function setInfo() {
        if ($this->id == "") {
            throw new Exception("Usuário inexistente.", 201);
        }
        $campos_sql = array("nome", "sexo", "senha", "email", "rg", "data_criacao", "image", "data_nascimento");
        $this->conn->prepareselect("usuario", $campos_sql, "id", $this->id);
        if (!$this->conn->executa()) {
            if ($this->conn->rowcount == 0) {
                throw new Exception("Usuário Inexistente", 201);
            }
            throw new Exception("Falha na comunicação do usuário.", 202);
        }
        $infos = $this->conn->fetch;
        $this->nome = $infos["nome"];
        $this->sexo = $infos["sexo"];
        $this->senha_hash = $infos["senha"];
        $this->email = $infos["email"];
        $this->rg = $infos["rg"];
        $this->data_criacao = $infos['data_criacao'];
        $this->data_nascimento = $infos['data_nascimento'];
        if ($infos["image"]) {
            $this->image = $infos["image"];
        } else {
            $this->image = "noimage.jpg";
        }
        $this->is_auth = true;


        $instanceController = new instanceController();
        $this->instances = $instanceController->loadUserInstance($this);
        if ($this->instances['count'] > 1) {
            foreach ($this->instances['instances'] as $index => $instance) {
                if ($instance['user_info']['instancia_padrao'] != 0) {
                    $this->instance = $instance;
                    $this->admin_info = $this->instance['user_info'];
                    break;
                }
            }
            if (!$this->instance) {
                $this->instance = $this->instances['instances'][0];
                $this->admin_info = $this->instance['user_info'];
            }
        } elseif ($this->instances['count'] == 1) {
            $this->instance = $this->instances['instance'];
            $this->admin_info = $this->instance['user_info'];
        }
    }

    /**
     * Pega todas as informações básicas do usuário e criar um Array.
     * @return Array
     * @throws Exception
     */
    public function getBasicInfo() {
        if ($this->is_auth == false) {
            throw new Exception("O usuário não está validado.");
        }

        $infos['id'] = $this->id;
        $infos["nome"] = $this->nome;
        $infos["sexo"] = $this->sexo;
        $infos["cidade"] = $this->cidade;
        $infos["estado"] = $this->estado;
        $infos["email"] = $this->email;
        $infos["rg"] = $this->rg;
        $infos['data_nascimento'] = $this->formatBirthDay($this->data_nascimento);
        $infos["evento_padrao"] = $this->admin_info["evento_padrao"];
        $infos["nome_instancia"] = $this->instance["nome"];
        $infos["id_instancia"] = $this->instance["id"];
        $infos["image"] = $this->image;

        return $infos;
    }
    
    public function formatBirthDay($birthday) {
        $array = Array("dia" => 01, "mes" => 06, "ano" => 1994, "full" => "01/06/1994");
        return $array;
    }

    private function updateSerializedUser() {
        $_SESSION['user'] = serialize($this);
    }

    /**
     * setId($id)
     * @param INTEGER $id
     * @throws Exception 101
     * 
     * Atribui a ID do usuário a partir do argumento oferecido.
     */
    public function setId($id) {
        if (!is_numeric($id)) {
            throw new Exception("ID não é válido.", 101);
        }

        $this->nome = "";
        $this->sexo = "";
        $this->cidade = "";
        $this->estado = "";
        $this->senha_hash = "";
        $this->email = "";
        $this->rg = "";
        $this->tipo = "";
        $this->admin_info = Array();
        $this->is_auth = false;
        $this->id = $id;
    }

    /**
     * isAuth()
     * @return boolean
     * 
     * Verifica se o usuário está autenticado, utilizando a flag is_auth.
     */
    public function isAuth() {
        if ($this->is_auth == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * setExternalInfo($infos)
     * @param Array $infos
     * @throws Exception 102,101,103,104,105
     * 
     * Atribui todas as informações do usuário, utilizando um array oferecido externamente.
     * NÃO REALIZA AUTENTICAÇÃO DO USUÁRIO
     */
    protected function setExternInfo($infos) {
        if (!is_array($infos)) {
            throw new Exception("Array inválido.", 102);
        }
        if (!is_numeric($infos['id'])) {
            throw new Exception("ID não é válido.", 101);
        }

        if (strlen($infos['password']) != 32) {
            throw new Exception("Senha inválida.", 103);
        }

        if (preg_match($this->email_patten, $infos['email'])) {
            throw new Exception("Email inválido", 105);
        }

        $this->id = $infos['id'];
        $this->tipo = $infos['tipo'];
        $this->email = $infos['email'];
        $this->status = $infos['status'];
        $this->senha_hash = $infos['password'];
    }

    /**
     * getId()
     * @return INTEGER
     * 
     * Retorna o ID do usuário.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * getPassword()
     * @return MD5 HASH
     * 
     * Retorna uma string contendo o HASH md5 da senha do usuário.
     */
    public function getPassword() {
        return $this->senha_hash;
    }

    /**
     * getEmail()
     * @return String
     * 
     * Retorna uma string contendo o email do usuário.
     */
    public function getEmail() {
        return $this->email;
    }

    public function getCreationDate($format = true) {
        if ($format == true) {
            $datetime = new DateTime($this->data_criacao);
            return $datetime->format("d/m/Y");
        } else {
            return $this->data_criacao;
        }
    }

    public function updateInfos($infos) {
        $fields = array();
        $values = array();
        if (!is_numeric($this->id)) {
            throw new Exception("Identificador do usuário inválido.", 101);
        }

        if (!is_array($infos)) {
            throw new Exception("Informações para edição inválidas.");
        }

        if ($infos['nome'] != $this->nome && !is_null($infos['nome'])) {
            if (trim($infos['nome']) == "" || !is_string($infos['nome'])) {
                throw new Exception("Nome informado inválido.");
            }
            $this->nome = $infos['nome'];
            $fields[] = "nome";
            $values[] = $infos['nome'];
        }

        if ($infos['rg'] != $this->rg && !is_null($infos['rg'])) {
            if (trim($infos['rg']) == "" || strlen($infos['rg']) > 20) {
                throw new Exception("RG informado inválido.");
            }
            $this->rg = $infos['rg'];
            $fields[] = "rg";
            $values[] = $infos['rg'];
        }


        if ($infos['email'] != $this->email && !is_null($infos['email'])) {
            if (!$this->validateEmail($infos['email'])) {
                throw new Exception("Email inválido. (" . $infos['email'] . ")", 105);
            }
            $this->email = $infos['email'];
            $fields[] = "email";
            $values[] = $infos['email'];
        }

        if ($infos['cidade'] != $this->cidade && !is_null($infos['cidade'])) {
            if (trim($infos['cidade']) == "" || strlen($infos['cidade']) > 100) {
                throw new Exception("Cidade informada inválida.");
            }
            $this->cidade = $infos['cidade'];
            $fields[] = "cidade";
            $values[] = $infos['cidade'];
        }


        if ($infos['estado'] != $this->estado && !is_null($infos['estado'])) {
            if (strlen($infos['estado']) > 2) {
                throw new Exception("Estado informado inválido.");
            }
            $this->estado = $infos['estado'];
            $fields[] = "estado";
            $values[] = $infos['estado'];
        }

        if (md5($infos['senha']) != $this->senha && $infos['senha'] != "") {
            $this->setPassword($infos['senha']);
        }

        $usercontroller = new userController();
        $user = $usercontroller->getUser();

        if ($user->getId() != $this->id && $user->getPermission() < $this->tipo) {
            throw new Exception("Usuário sem permissão de alterar o perfil.");
        }
        if ($user->getPermission() >= 5) {

            if ($infos['sexo'] != $this->sexo && !is_null($infos['sexo'])) {
                if ($infos['sexo'] != 'm' && $infos['sexo'] != 'f') {
                    throw new Exception("Sexo informado inválida.");
                }
                $this->sexo = $infos['sexo'];
                $fields[] = "sexo";
                $values[] = $infos['sexo'];
            }
        }
        if (count($fields) < 1) {
            return;
        }


        $this->conn->prepareupdate($values, $fields, "usuario", $this->id, "id");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível atualizar as informações do usuário.");
        }

        if ($user->getId() == $this->id) {
            $_SESSION['user'] = serialize($this);
        }
    }

    /**
     * setEmail()
     * @param String $email
     * @throws Exception 105
     * 
     * Atribui valor para o email do usuário.
     */
    public function setEmail($email) {
        if (!preg_match($this->email_patten, $email)) {
            throw new Exception("Email inválido.", 105);
        }

        $this->email = $email;
    }

    /**
     * setPassword()
     * @param type $pw
     * @throws Exception
     * 
     * Altera a senha do usuário.
     */
    public function setPassword($pw) {
        /**
         *  Altera a senha atual do user.
         */
        if (!$this->isAuth()) {
            throw new Exception("O usuário não está logado", 203);
        }

        if (strlen($pw) < 6) {
            throw new Exception("Digite uma senha superior a 6 caracteres.", 106);
        }

        $pw = md5($pw);
        $this->senha_hash = $pw;

        $this->conn->prepareupdate($pw, "senha", "usuario", $this->id, "id", "STR");
        if (!$this->conn->executa()) {
            throw new Exception("Erro ao realizar ação.", 001);
        }
    }

    public function setSelectedEvent($event_id) {

        if (!is_numeric($event_id)) {
            throw new Exception("ID do evento em formato inválido.");
        }

        if ($this->tipo < DYON_USER_ADMIN || !$this->isAuth()) {
            throw new Exception("Usuário não tem permissão para realizar a ação.");
        }

        $instance = $this->getUserInstance();

        $this->conn->prepareselect("evento", "id_instancia", "id", $event_id);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível pesquisar a diretoria selecionada.");
        }

        if ($this->conn->fetch["id_instancia"] != $instance["id"]) {
            throw new Exception("A instância do evento é diferente da instância adicionada.");
        }

        $this->conn->prepareupdate($event_id, "evento_padrao", "instancia_usuario", array($this->id, $instance['id']), array("id_usuario", "id_instancia"), "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o evento.");
        }

        $this->admin_info['evento_padrao'] = $event_id;
        $this->updateSerializedUser();
    }

    public function setSelectedBoard($board_id) {
        if (!is_numeric($board_id)) {
            throw new Exception("ID do diretoria em formato inválido.");
        }

        if ($this->tipo < DYON_USER_ADMIN || !$this->isAuth()) {
            throw new Exception("Usuário não tem permissão para realizar a ação.");
        }

        $this->conn->prepareselect("board", "id_instancia", "id", $board_id);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível pesquisar a diretoria selecionada.");
        }

        $instance = $this->getUserInstance();
        if ($this->conn->fetch['id_instancia'] != $instance['id']) {
            throw new Exception("O usuário não está na mesma instância da diretoria selecionada.");
        }

        $this->conn->prepareupdate($board_id, "board_padrao", "instancia_usuario", array($this->id, $instance['id']), array("id_usuario", "id_instancia"), "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar a diretoria.");
        }

        $this->admin_info['board_padrao'] = $board_id;
        $this->updateSerializedUser();
    }

    public function setSelectedFlow($flow_id) {
        if ($flow_id != 1 && $flow_id != 2 && $flow_id != 3) {
            throw new Exception("O fluxo de caixa está em formato inválido..");
        }

        if ($this->tipo < DYON_USER_ADMIN || !$this->isAuth()) {
            throw new Exception("Usuário não tem permissão para realizar a ação.");
        }

        $instance = $this->getUserInstance();
        $this->conn->prepareupdate($flow_id, "fluxo_padrao", "instancia_usuario", array($this->id, $instance['id']), array("id_usuario", "id_instancia"), "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar a interface do fluxo de caixa.");
        }

        $this->admin_info['fluxo_padrao'] = $flow_id;
        $this->updateSerializedUser();
    }

    public function getSelectedEvent() {

        if (!isset($this->admin_info['evento_padrao'])) {
            throw new Exception("Evento não encontrado.");
        }

        return $this->admin_info['evento_padrao'];
    }

    public function getSelectedBoard() {
        if (!isset($this->admin_info['board_padrao'])) {
            throw new Exception("Diretoria não encontrada.");
        }

        return $this->admin_info['board_padrao'];
    }

    public function getSelectedFlow() {
        if (!isset($this->admin_info['fluxo_padrao'])) {
            throw new Exception("Interface não encontrada.");
        }

        return $this->admin_info['fluxo_padrao'];
    }

    /**
     *  Verifica se o e-mail em questão é disponível.
     */
    public function validateEmail($email) {

        if (!preg_match($this->email_pattern, $email)) {
            return false;
        }

        $this->conn->prepareselect("user", "id", "email", $email);
        $this->conn->executa();
        if ($this->conn->rowcount != 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *  Retorna a instância do usuário
     */
    public function getUserInstance() {
        if (!$this->is_auth || !is_array($this->instance)) {
            throw new Exception("O usuário não está logado me nenhuma instância.");
        }

        if (!is_numeric($this->instance['id'])) {
            throw new Exception("O identificador da instância selecionado é inválido.");
        }

        if ($this->instance["user_info"]["status_usuario"] <= 0) {
            throw new Exception("O usuário não está habilitado a carregar a instância.");
        }

        return $this->instance;
    }

    /**
     *  Retorna todas as instâncias do usuário
     */
    public function getUserInstances() {
        if (!$this->is_auth || !is_array($this->instances)) {
            throw new Exception("O usuário não possui instâncias");
        }

        return $this->instances;
    }

    public function setCurrentInstance($instance_id) {
        if (!is_numeric($instance_id)) {
            throw new Exception("O identificador da instância não está no formado adequado.");
        }

        $changed = false;

        foreach ($this->instances["instances"] as $index => $instance) {
            if ($instance['id'] == $instance_id) {
                $this->instance = $instance;
                $this->admin_info = $instance["user_info"];

                $instancecontroller = new instanceController();
                $instancecontroller->setDefaultInstance($this, $this->instance);
                $changed = true;
            }
        }

        if (!$changed) {
            throw new Exception("Não foi possível alterar a instância.");
        }

        $this->updateSerializedUser();
    }

}

?>