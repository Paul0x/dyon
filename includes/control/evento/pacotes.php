<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: pacotes.php *  Type: Controller *  ===================================================================== *  */define("DYON_PACOTE_STATUS_CANCELADO", 0);define("DYON_PACOTE_STATUS_PENDENTE", 1);define("DYON_PACOTE_STATUS_APROVADO", 2);define("DYON_PACOTE_STATUS_QUITADO", 3);define("DYON_PACOTE_STATUS_CADASTRADO", 4);define("DYON_PARCELA_STATUS_CANCELADO", 0);define("DYON_PARCELA_STATUS_PENDENTE", 1);define("DYON_PARCELA_STATUS_APROVADO", 2);define("DYON_PARCELA_STATUS_AGUARDANDO", 3);require_once(config::$syspath."includes/sql/pacotes.php");require_once(config::$syspath."includes/control/evento/grupos.php");require_once(config::$syspath."includes/lib/mailer.php");class pacoteController {    private $lote_id;    private $conn;    private $is_user;    private $user;    public function __construct($conn) {        $this->conn = $conn;        try {            $usercontroller = new userController();            $this->user = $usercontroller->getUser(1);        } catch (Exception $error) {            $this->is_user = false;        }    }    public function listPacotes($filters) {        $available_filters = Array("nome", "grupo", "rg", "cidade", "estado");        $orderable_fields = Array("nome", "grupo", "lote", "pagamento", "status", "valor");        if (!in_array($filters['field']['label'], $available_filters)) {            throw new Exception("Sua pesquisa de pacotes precisa ter algum filtro.");        }        if (!is_numeric($filters['id_evento'])) {            throw new Exception("ID do evento inválido.");        }        if (!is_numeric($filters['page']) || $filters['page'] <= 0) {            $filters['page'] = 0;        }        if (is_array($filters['order'])) {            foreach ($filters['order'] as $index => $order) {                if ($order['mode'] != 'ASC' && $order['mode'] != 'DESC') {                    $order['mode'] = "ASC";                }                if (is_null($order['field']) || !in_array($order['field'], $orderable_fields)) {                    $order['field'] = 'd.nome';                }                switch ($order['field']) {                    case "nome":                        $filters['order'][$index]['field'] = "d.nome";                        break;                    case "grupo":                        $filters['order'][$index]['field'] = "b.nome";                        break;                    case "lote":                        $filters['order'][$index]['field'] = "e.nome";                        break;                    case "pagamento":                        $filters['order'][$index]['field'] = "a.tipo_pagamento";                        break;                    case "status":                        $filters['order'][$index]['field'] = "a.status";                        break;                    case "valor":                        $filters['order'][$index]['field'] = "SUM(c.valor)";                        break;                }            }        }        $this->pacotemodel = new pacoteModel($this->conn);        $pacotes = $this->pacotemodel->listPacotesQuery($filters);        foreach ($pacotes['list'] as $index => $pacote) {            switch ($pacote[3]) {                case 1:                    $pacotes['list'][$index][3] = "Boleto";                    break;                case 2:                    $pacotes['list'][$index][3] = "Pagseguro";                    break;                case 3:                    $pacotes['list'][$index][3] = "Depósito";            }            $pacotes['list'][$index][2] = $pacotes['list'][$index][2];            $pacotes['list'][$index][5] = "R$" . number_format($pacotes['list'][$index][5], 2, ",", ".");        }        return $pacotes;    }    public function listPacotesByUserId($user) {        if (!is_object($user)) {            throw new Exception("Usuário informado inválido para listar pacotes.");        }        $id = $user->getId();        $this->pacotemodel = new pacoteModel($this->conn);        $pacotes = $this->pacotemodel->listPacotesByUser($id);        foreach ($pacotes['list'] as $index => $pacote) {            switch ($pacote['tipo_pagamento']) {                case 1:                    $pacotes['list'][$index]['tipo_pagamento'] = "Boleto";                    break;                case 2:                    $pacotes['list'][$index]['tipo_pagamento'] = "Pagseguro";                    break;                case 3:                    $pacotes['list'][$index]['tipo_pagamento'] = "Depósito";                    break;            }            $pacotes['list'][$index]['valor_total'] = "R$" . number_format($pacotes['list'][$index]['valor_total'], 2, ",", ".");        }        return $pacotes;    }    public function listPacotesByParcela($filters) {        $pacotemodel = new pacoteModel($this->conn);        $pacotes = $pacotemodel->listPacotesByParcela($filters);        foreach ($pacotes['list'] as $index => $pacote) {            $pacotes['list'][$index][3] = "R$" . number_format($pacote[3], 2, ',', '.');        }        return $pacotes;    }    public function listPacoteByGroups($filters) {        $pacotemodel = new pacoteModel($this->conn);        $groups = $pacotemodel->listPacoteByGroups($filters);        foreach ($groups as $index => $group) {            $i = 0;            foreach ($group['members'] as $idx => $member) {                $i++;            }            $groups[$index]['num_membros'] = $i;        }        return $groups;    }    public function loadPacoteById($id_pacote) {        if (!is_numeric($id_pacote)) {            throw new Exception("Identificador do pacote em formato inválido.");        }        $this->pacotemodel = new pacoteModel($this->conn);        $pacote = $this->pacotemodel->loadPacoteById($id_pacote);        $pacote['tipo_pagamento_id'] = $pacote['tipo_pagamento'];        switch ($pacote['tipo_pagamento']) {            case 1:                $pacote['tipo_pagamento'] = "Boleto";                break;            case 2:                $pacote['tipo_pagamento'] = "Pagseguro";                break;            case 3:                $pacote['tipo_pagamento'] = "Depósito";                break;        }        $pacote['valor_total'] = "R$" . number_format($pacote['valor_total'], 2, ",", ".");        return $pacote;    }    public function addPacote($pacote = "") {        if (!is_array($pacote)) {            $filters = Array(                "evento" => FILTER_VALIDATE_INT,                "lote" => FILTER_VALIDATE_INT,                "desconto" => FILTER_SANITIZE_SPECIAL_CHARS,                "cliente" => FILTER_VALIDATE_INT,                "grupo_add" => FILTER_SANITIZE_SPECIAL_CHARS,                "grupo_select" => FILTER_VALIDATE_INT,                "pagamento" => FILTER_VALIDATE_INT,                "parcelas" => FILTER_VALIDATE_INT            );            $pacote = filter_input_array(INPUT_POST, $filters);        }        foreach ($pacote as $index => $field) {            if ((is_null($field) || !$field || !is_numeric($field)) && ($index != "desconto" && $index != "grupo_add" && $index != "grupo_select")) {                throw new Exception("Campo $index em branco.");            }        }        if (!is_numeric($pacote['desconto']) || $pacote['desconto'] >= 100 || $pacote['desconto'] <= 0) {            $pacote['desconto'] = 0;        }        if (is_null($pacote['grupo_add']) && is_null($pacote['grupo_select'])) {            throw new Exception("Selecione ou adicione um grupo válido.");        }        $eventcontroller = new eventController();        try {            $event = $eventcontroller->loadEvent($pacote['evento'], 2, true);        } catch (Exception $error) {            throw new Exception("Evento inválido ou arquivado.");        }        $lote_found = false;        $lote_selected = array();        foreach ($event['lista_lotes'] as $index => $lote) {            if ($lote['id'] == $pacote['lote']) {                if ($lote['status'] != 2) {                    throw new Exception("O lote selecionado está fechado.");                }                if ($lote['max_venda'] == $lote['vendidos']) {                    throw new Exception("Esse lote já está em seu limite máximo de vendas.");                }                $lote_selected = $lote;                $lote_found = true;            }        }        if ($lote_found == false) {            throw new Exception("Lote não encontrado no evento selecionado.");        }        $usercontroller = new userController();        $user = $usercontroller->getUser(0, false);        $user->setId($pacote['cliente']);        $user->setInfo();        $user_info = $user->getBasicInfo();        if (!$this->checkUserPacotes($user->getId(), $pacote["evento"])) {            throw new Exception("Você já possui um pacote pendente para esse evento, cancele a compra atual para criar outra.");        }        $grupocontroller = new grupoController($this->conn);        if (!is_null($pacote['grupo_add'])) {            $grupo_selected = $grupocontroller->addGroup($user, $event, $pacote['grupo_add']);        } elseif (!is_null($pacote['grupo_select'])) {            $grupocontroller->verifyGroup($pacote['grupo_select'], $event['id']);            $grupo_selected = $pacote['grupo_select'];        }        if (!is_numeric($grupo_selected)) {            throw new Exception("Grupo inválido para criar pacote.");        }        $query['fields'] = Array("id_usuario", "id_grupo", "id_lote", "desconto", "tipo_pagamento", "status");        $query['values'] = Array($user->getId(), $grupo_selected, $lote_selected['id'], $pacote['desconto'], $pacote['pagamento'], 1);        $this->conn->prepareinsert("pacote", $query['values'], $query['fields']);        if (!$this->conn->executa()) {            throw new Exception("Impossível adicionar pacote.");        }        $id_pacote = $this->conn->pegarMax("pacote") - 1;        if ($pacote['desconto'] == 0) {            $valor_pacote = $lote_selected[2];        } else {            $valor_pacote = ($lote_selected[2] / 100) * ( 100 - $pacote['desconto']);        }        $valor_parcela = number_format($valor_pacote / $pacote['parcelas'], 2, ".", "");        $datetime = new DateTime();        $interval = new DateInterval("P1M");        $datetime_start = new DateTime($event['data_inicio']);        $datetime_start->modify("-5 Days");        for ($i = 0; $i < $pacote['parcelas']; $i++) {            if ($i != 0) {                $datetime->add($interval);            }            if ($datetime > $datetime_start) {                $date_string = $datetime_start->format("Y-m-d H:i:s");            } else {                $date_string = $datetime->format("Y-m-d H:i:s");            }            $parcela_query["values"] = Array($id_pacote, $valor_parcela, $date_string, 1);            $parcela_query["fields"] = Array("id_pacote", "valor", "data_vencimento", "status");            $this->conn->prepareinsert("parcela_pacote", $parcela_query['values'], $parcela_query['fields']);            if (!$this->conn->executa()) {                throw new Exception("Erro ao adicionar parcelas.");            }        }        $this->updatePacoteDate($id_pacote);        $vars['user'] = $user_info;        try {            mail::sendTemplateEmail("pacote_add_success_mail", $vars, $user_info['email'], "Confirmação de Pedido [ CarnaBoemia 2016 ]");        } catch (Exception $error) {                    }        return $id_pacote;    }    private function checkUserPacotes($user_id, $event_id) {        $this->pacotemodel = new pacoteModel($this->conn);        try {            $pacotes = $this->pacotemodel->listPacotesByUser($user_id, 1, $event_id);            if (count($pacotes) != 0) {                return false;            } else {                return true;            }        } catch (Exception $error) {            return true;        }    }    public function changePacoteStatus($pacote_id, $status) {        if (!is_numeric($pacote_id) || ($status != 1 && $status != 2 && $status != 0 && $status != 4) || !is_numeric($status)) {            throw new Exception("Informações do pacote inválidas.");        }        $this->conn->prepareupdate($status, "status", "pacote", $pacote_id, "id", "INT");        if (!$this->conn->executa()) {            throw new Exception("Não foi possível alterar o status do pacote.");        }        $pacote = $this->loadPacoteById($pacote_id);        if ($status == 2 || $status == 3) {            $pacote = $this->checkPacoteStatus($pacote, "id_pacote", "status_pacote");        } else {            $pacote["status_pacote"] = $status;        }        $this->updatePacoteDate($pacote_id);        return $pacote["status_pacote"];    }    public function getParcelas($pacote_id) {        if (!is_numeric($pacote_id)) {            throw new Exception("Identificador do pacote inválido.");        }        $fields = Array("id", "id_pacote", "data_criacao", "data_vencimento", "valor", "status");        $this->conn->prepareselect("parcela_pacote", $fields, "id_pacote", $pacote_id, "", "", "", PDO::FETCH_ASSOC, "all");        if (!$this->conn->executa() || $this->conn->rowcount == 0) {            throw new Exception("Não foram encontradas parcelas para esse pacote.");        }        $parcelas = $this->conn->fetch;        foreach ($parcelas as $index => $parcela) {            $datetime = new DateTime($parcela['data_criacao']);            $parcelas[$index]['data_criacao'] = $datetime->format("d/m/Y");            $datetime = new DateTime($parcela['data_vencimento']);            $parcelas[$index]['data_vencimento'] = $datetime->format("d/m/Y");            $parcelas[$index]['valor'] = "R$" . number_format($parcela['valor'], 2, ',', '.');        }        return $parcelas;    }    public function getParcelaById($parcela_id) {        if (!is_numeric($parcela_id)) {            throw new Exception("Identificador da parcela inválido.");        }        $fields = Array("id", "id_pacote", "data_criacao", "data_vencimento", "valor", "status", "id_comprovante", "tipo_comprovante");        $this->conn->prepareselect("parcela_pacote", $fields, "id", $parcela_id);        if (!$this->conn->executa() || $this->conn->rowcount == 0) {            throw new Exception("Nenhuma parcela encontrada com essas informações");        }        $parcela = $this->conn->fetch;        $datetime = new DateTime($parcela['data_criacao']);        $parcela['data_criacao'] = $datetime->format("d/m/Y");        $datetime = new DateTime($parcela['data_vencimento']);        $parcela['data_vencimento'] = $datetime->format("d/m/Y");        $parcela['valor'] = "R$" . number_format($parcela['valor'], 2, ',', '.');        return $parcela;    }    public function parcelaConfirm($parcela_id, $comprovante, $tipo = 2, $tipo_comprovante = 1, $user = null) {        if ($user == null) {            $usercontroller = new userController();            $user = $usercontroller->getUser();            $user_out = false;        } else {            if (!is_object($user) || !$user->isAuth()) {                throw new Exception("Usuário inválido.");            }            $user_out = true;        }        $user_info = $user->getBasicInfo();        if ($user_info['tipo'] <= 1 && $tipo == 2 && $user_out == false) {            throw new Exception("Você não tem permissão para confirmar uma parcela.");        }        if (!is_numeric($parcela_id)) {            throw new Exception("Identificado da parcela inválido.");        }        $fields = Array("id", "id_pacote", "data_criacao", "data_vencimento", "valor", "status", "id_comprovante");        $this->conn->prepareselect("parcela_pacote", $fields, "id", $parcela_id);        if (!$this->conn->executa() || $this->conn->rowcount == 0) {            throw new Exception("Nenhuma parcela encontrada com essas informações.");        }        $parcela = $this->conn->fetch;        if ($parcela['status'] == 0) {            throw new Exception("Você não pode alterar uma parcela cancelada.");        } else if ($parcela['status'] == 2) {            throw new Exception("A parcela já está paga.");        }        if ($tipo != 2 && $tipo != 3) {            throw new Exception("Status da alteração da parcela é inválido.");        }        $datetime = new DateTime();        $update_fields = Array("status", "data_pagamento");        $update_values = Array($tipo, $datetime->format("Y-m-d H:i:s"));        $update_bind = Array("INT", "STR");        /* Separar as verificações abaixo em outra função */        if (is_null($parcela['id_comprovante'])) {            if ($tipo_comprovante != 2 && $tipo_comprovante != 1) {                throw new Exception("O tipo de comprovante informado é falso.");            }            if ($tipo_comprovante == 1) {                if (!is_file($comprovante['tmp_name']) || ($comprovante['type'] != "image/jpeg" && $comprovante['type'] != "image/jpg" && $comprovante['type'] != "image/png" && $comprovante['type'] != "application/pdf")) {                    throw new Exception("Comprovante inválido.");                }                if (!preg_match("`^[-0-9A-Z_\.]+$`i", basename($comprovante['tmp_name'])) || strlen(basename($comprovante['tmp_name'])) > 100) {                    throw new Exception("Nome do arquivo inválido ou muito grande.");                }                switch ($comprovante['type']) {                    case "image/jpeg":                    case "image/jpg":                        $end = "jpg";                        break;                    case "image/png":                        $end = "png";                        break;                    case "application/pdf":                        $end = "pdf";                }                $comprovante_name = uniqid($parcela['id'] . "_") . "." . $end;                if (!move_uploaded_file($comprovante['tmp_name'], "./comprovante/parcela/" . $comprovante_name)) {                    throw new Exception("Não foi possível carregar o comprovante.");                }                $update_fields[] = "id_comprovante";                $update_values[] = $comprovante_name;                $update_fields[] = "tipo_comprovante";                $update_values[] = 1;                $update_bind[] = "STR";                $update_bind[] = "INT";            } else if ($tipo_comprovante == 2) {                if (!is_string($comprovante) || strlen($comprovante) > 100 || $comprovante == "") {                    throw new Exception("Código da transação inválido.");                }                $update_fields[] = "id_comprovante";                $update_values[] = $comprovante;                $update_fields[] = "tipo_comprovante";                $update_values[] = 2;                $update_bind[] = "STR";                $update_bind[] = "INT";            }        }        $this->conn->prepareupdate($update_values, $update_fields, "parcela_pacote", $parcela['id'], "id", $update_bind);        if (!$this->conn->executa()) {            throw new Exception("Não foi possível confirmar o pacote.");        }        if ($tipo == 2) {            try {                $this->conn->prepareselect("pacote", "id_usuario", "id", $parcela['id_pacote']);                $this->conn->executa();                $user_parcela = $usercontroller->getUser(0, false);                $user_parcela->setId($this->conn->fetch[0]);                $user_parcela->setInfo();                $user_info = $user_parcela->getBasicInfo();                $vars['user'] = $user_info;                $vars['parcela'] = $parcela;                mail::sendTemplateEmail("parcela_confirm_success", $vars, $user_info['email'], "Confirmação de Parcela [ CarnaBoemia 2016 ]");            } catch (Exception $error) {                            }        }        $pacote = $this->loadPacoteById($parcela['id_pacote']);        $this->checkPacoteStatus($pacote, "id_pacote", "status_pacote");        return $parcela;    }    public function changeParcelaStatus($status, $parcela_id, $parcela = null) {        if (!is_numeric($parcela_id)) {            throw new Exception("Identificador da parcela inválido.");        }        if ($status != 0 && $status != 1 && $status != 2 && $status != 3) {            throw new Exception("Status para troca é inválido.");        }        if (!is_array($parcela) || is_null($parcela['status']) || is_null($parcela['id_pacote'])) {            $this->conn->prepareselect("parcela_pacote", array("status", "id_pacote"), "id", $parcela_id);            if (!$this->conn->executa() || $this->conn->rowcount == 0) {                throw new Exception("Nenhuma parcela encontrada com essas informações.");            }            $parcela = $this->conn->fetch;        }        if ($parcela['status'] == 0) {            throw new Exception("Você não pode alterar uma parcela cancelada.");        }        if ($status == 0) {            $this->conn->prepareselect("parcela_pacote", "count(status)", array("id_pacote", "status"), array($parcela["id_pacote"], 0), array("=", "!="));            if (!$this->conn->executa()) {                throw new Exception("Não foi possível encontrar as parcelas do pacote.");            }            if ($this->conn->fetch[0] <= 1) {                throw new Exception("Você não pode cancelar todas as parcelas de um pacote.");            }        }        $update_fields = "status";        $update_values = $status;        $this->conn->prepareupdate($update_values, $update_fields, "parcela_pacote", $parcela_id, "id");        if (!$this->conn->executa()) {            throw new Exception("Não foi possível confirmar o pacote.");        }        $pacote = $this->loadPacoteById($parcela['id_pacote']);        $this->checkPacoteStatus($pacote, "id_pacote", "status_pacote");        return $parcela;    }    private function checkPacoteStatus($pacote, $id_index = "id", $status_index = "status") {        if (!is_numeric($pacote[$id_index])) {            throw new Exception("Identificador do pacote inválido.");        }        $pacote_status = $pacote[$status_index];        $fields = Array("id", "id_pacote", "data_criacao", "data_vencimento", "valor", "status", "id_comprovante");        $this->conn->prepareselect("parcela_pacote", $fields, "id_pacote", $pacote[$id_index], "", "", "", PDO::FETCH_ASSOC, "all");        if (!$this->conn->executa() || $this->conn->rowcount < 1) {            throw new Exception("Nenhuma parcela encontrada para o pacote.");        }        $parcelas = $this->conn->fetch;        $num_parcelas = count($parcelas);        $parcelas_status = Array(            "canceladas" => 0,            "pendentes" => 0,            "confirmadas" => 0,            "aguardando" => 0,        );        foreach ($parcelas as $index => $parcela) {            switch ($parcela['status']) {                case 0:                    $parcelas_status['canceladas'] ++;                    break;                case 1:                    $parcelas_status['pendentes'] ++;                    break;                case 2:                    $parcelas_status['confirmadas'] ++;                    break;                case 3:                    $parcelas_status['aguardando'] ++;                    break;            }        }        if ($pacote[$status_index] == DYON_PACOTE_STATUS_PENDENTE && $parcelas_status['confirmadas'] != 0) {            $this->updateDataAprovacao($pacote[$id_index]);            $pacote[$status_index] = DYON_PACOTE_STATUS_APROVADO;        }        if ($pacote[$status_index] == DYON_PACOTE_STATUS_APROVADO && $num_parcelas - $parcelas_status['canceladas'] == $parcelas_status['confirmadas']) {            $pacote[$status_index] = DYON_PACOTE_STATUS_QUITADO;        } else if ($pacote[$status_index] == DYON_PACOTE_STATUS_QUITADO && $num_parcelas != $parcelas_status['confirmadas']) {            $pacote[$status_index] = DYON_PACOTE_STATUS_APROVADO;        }        $this->conn->prepareupdate($pacote[$status_index], "status", "pacote", $pacote[$id_index], "id", "INT");        if (!$this->conn->executa()) {            throw new Exception("Não foi possível alterar o status do pacote.");        }        $this->conn->prepareselect("pacote", "id_lote", "id", $pacote[$id_index]);        if (!$this->conn->executa()) {            throw new Exception("Não foi possível verificador o ID do lote.");        }        $lote_id = $this->conn->fetch['id_lote'];        $lotecontroller = new loteController($this->conn);        $lotecontroller->checkLoteStatus($lote_id);        $this->updatePacoteDate($pacote[$id_index]);        return $pacote;    }        private function updateDataAprovacao($pacote_id) {        if(!is_numeric($pacote_id)) {            throw new Exception("Identificador do pacote inválido.");        }                $date = new DateTime();        $formatted_date = $date->format("Y-m-d h:i:s");        $this->conn->prepareupdate($formatted_date, "data_aprovacao", "pacote", $pacote_id, "id");        if(!$this->conn->executa()) {            throw new Exception("Não foi possível alterar a data de aprovação do pacote.");        }    }    public function addParcela($valor, $vencimento, $pacote_id) {        if (!is_numeric($pacote_id)) {            throw new Exception("O identificado do pacote é inválido.");        }        if (!is_numeric($valor) || $valor == 0) {            throw new Exception("O valor do pacote está em formato incorreto.");        }        $vencimento = explode("/", $vencimento);        if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {            throw new Exception("Data de vencimento em formato inválido.");        }        $datetime = new DateTime();        $datetime->setDate($vencimento[2], $vencimento[1], $vencimento[0]);        $datetime->setTime(23, 59, 59);        $datetime_tomorrow = new DateTime("tomorrow");        if ($datetime_tomorrow > $datetime) {            throw new Exception("Você precisa especificar uma data maior do que o dia seguinte para o vencimento.");        }        $parcela_query['fields'] = Array("valor", "data_vencimento", "id_pacote", "status");        $parcela_query['values'] = Array($valor, $datetime->format("Y-m-d H:i:s"), $pacote_id, 1);        $this->conn->prepareinsert("parcela_pacote", $parcela_query['values'], $parcela_query['fields']);        if (!$this->conn->executa()) {            throw new Exception("Erro ao adicionar parcelas.");        }        $this->updatePacoteDate($pacote_id);    }    public function editParcela($valor, $vencimento, $parcela_id) {        if (!is_numeric($parcela_id)) {            throw new Exception("O identificado do pacote é inválido.");        }        if (!is_numeric($valor) || $valor == 0) {            throw new Exception("O valor do pacote está em formato incorreto.");        }        $vencimento = explode("/", $vencimento);        if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {            throw new Exception("Data de vencimento em formato inválido.");        }        $datetime = new DateTime();        $datetime->setDate($vencimento[2], $vencimento[1], $vencimento[0]);        $datetime->setTime(23, 59, 59);        $parcela_query['fields'] = Array("valor", "data_vencimento");        $parcela_query['values'] = Array($valor, $datetime->format("Y-m-d H:i:s"));        $this->conn->prepareupdate($parcela_query['values'], $parcela_query['fields'], "parcela_pacote", $parcela_id, "id");        if (!$this->conn->executa()) {            throw new Exception("Erro ao editar parcela.");        }        $parcela = $this->getParcelaById($parcela_id);        $this->updatePacoteDate($parcela['id_pacote']);        return $parcela;    }    private function updatePacoteDate($pacote_id) {        if (!is_numeric($pacote_id)) {            throw new Exception("Identificador do pacote é inválido.");        }        $datetime = new DateTime();        $this->conn->prepareupdate($datetime->format("Y-m-d H:i:s"), "data_alteracao", "pacote", $pacote_id, "id", "INT");        if (!$this->conn->executa()) {            throw new Exception("Não foi possível alterar a última edição do pacote.");        }    }    public function getGroupMembers($id_grupo) {        $grupocontroller = new grupoController($this->conn);        $members = $grupocontroller->getGroupMembers($id_grupo);        return $members;    }    public function pacoteEditInfo($infos) {        if (!is_numeric($infos['id'])) {            throw new Exception("Identificador do pacote inválido.");        }        $grupocontroller = new grupoController($this->conn);        $pacote = $this->loadPacoteById($infos['id']);        $values = Array();        $fields = Array();        $bind = Array();        if (is_numeric($infos['lote']) && $infos['lote'] != $pacote['id_lote']) {            $fields[] = "id_lote";            $values[] = $infos['lote'];            $bind[] = "INT";        }        if ($infos['grupo'] != "") {            try {                $grupo = $grupocontroller->getGroupByCod($infos['grupo']);                $grupo_old = $grupocontroller->loadGroupInfo($pacote['id_grupo']);                                /**                 *  Checagem se o usuário é líder do grupo.                 */                if ($grupo_old['id_lider'] == $pacote['id_usuario']) {                    $members_count = 0;                    $new_leader;                                        foreach($grupo_old['members'] as $index => $member) {                        if($member['id'] != $pacote['id']) {                            $members_count++;                            $new_leader = $member['id_usuario'];                        }                                                if($members_count != 0) {                            $grupocontroller->editGroup(Array("id" => $grupo_old['id'], "new_lider" => $new_leader));                        }                    }                }                if ($grupo['id'] != $grupo_old['id']) {                    $fields[] = "id_grupo";                    $values[] = $grupo['id'];                    $bind[] = "INT";                    $new_group = true;                }            } catch (Exception $ex) {                throw new Exception($ex->getMessage());            }        }        if (count($fields) == 0) {            return $pacote;        }        $this->conn->prepareupdate($values, $fields, "pacote", $pacote['id_pacote'], "id", $bind);        if (!$this->conn->executa()) {            throw new Exception("Não foi possível editar o pacote.");        }        $new_pacote = $this->loadPacoteById($infos['id']);        if ($new_group == true) {            $new_pacote['new_group'] = true;        }        return $new_pacote;    }        public function getPacoteByCasas($pacotes) {        if(!is_array($pacotes)) {            throw new Exception("Os pacotes estão em formato inválido.");        }                $model = new pacoteModel($this->conn);        $rooms = $model->listPacotesByHouse($pacotes);                return $rooms;    }}