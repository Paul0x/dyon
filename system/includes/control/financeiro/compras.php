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
 *  File: compras.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/finances.php");
define("DYON_COMPRAS_STATUS_CANCELADO", 0);
define("DYON_COMPRAS_STATUS_PENDENTE", 1);
define("DYON_COMPRAS_STATUS_APROVADO", 2);
define("DYON_COMPRAS_STATUS_QUITADO", 3);

define("DYON_PARCELAC_STATUS_CANCELADO", 0);
define("DYON_PARCELAC_STATUS_PENDENTE", 1);
define("DYON_PARCELAC_STATUS_APROVADO", 2);

define("DYON_COMPRAS_TIPO_COMPRA", 0);
define("DYON_COMPRAS_TIPO_ORCAMENTO", 1);

class comprasController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    public function addCompra($compra) {
        $usercontroller = new userController();
        $user = $usercontroller->getUser(DYON_USER_ADMIN);
        $user->getAdminInfo();
        $compra['id_evento'] = $user->getSelectedEvent();
        $compra['nome'] = trim($compra['nome']);
        if (!is_numeric($compra['id_evento'])) {
            throw new Exception("ID do evento incorreto.");
        }
        $compra['status'] = DYON_COMPRAS_STATUS_PENDENTE;
        if (!is_array($compra['parcelas'])) {
            throw new Exception("Formato das parcelas inválidos.");
        }
        $total_parcelas = 0;
        foreach ($compra['parcelas'] as $index => $parcela) {
            $compra['parcelas'][$index]['valor'] = $parcela['valor'] = str_replace(",", ".", $parcela['valor']);
            $compra['parcelas'][$index]['status'] = DYON_PARCELAC_STATUS_PENDENTE;
            $this->validateParcela($parcela);
            $total_parcelas+= $parcela["valor"];
        }

        if ($total_parcelas - ($compra["valor_unitario"] * $compra["quantidade"]) != 0) {
            echo $total_parcelas - ($compra["valor_unitario"] * $compra["quantidade"]);
            throw new Exception("O valor total das parcelas deve ser igual ao valor unitário * quantidade.");
        }

        if (!$this->checkCategoria($compra['categoria'])) {
            throw new Exception("Categoria da compra inválida.");
        }
        $query['fields'] = Array("nome", "quantidade", "valor_unitario", "tipo", "id_categoria", "status", "id_evento");
        $query['values'] = Array($compra['nome'], $compra['quantidade'], $compra['valor_unitario'], $compra['tipo'], $compra['categoria'], $compra['status'], $compra['id_evento']);
        $this->conn->prepareinsert("compra", $query['values'], $query['fields']);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar a compra.");
        }
        $compra_id = $this->conn->pegarMax("compra") - 1;
        foreach ($compra['parcelas'] as $index => $parcela) {
            $this->addParcela($compra_id, $parcela);
        }
    }

    public function addParcela($compra_id, $parcela, $refresh = false) {

        $query['fields'] = Array("id_compra", "valor", "status");
        $query['values'] = Array($compra_id, $parcela['valor'], $parcela['status']);

        $this->validateParcela($parcela);
        if (!is_null($parcela['vencimento']) && $parcela['vencimento'] != "") {
            $datetime = new DateTime();
            $date = explode("/", $parcela['vencimento']);
            if (!checkdate($date[1], $date[0], $date[2])) {
                throw new Exception("Data de vencimento inválida.");
            }
            $datetime->setDate($date[2], $date[1], $date[0]);
            $datetime->setTime(23, 59, 00);
            $query['fields'][] = "data_vencimento";
            $query['values'][] = $datetime->format("Y-m-d H:i:s");
        }

        $this->conn->prepareinsert("parcela_compra", $query['values'], $query['fields']);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar parcela da compra.");
        }
        
        if($refresh) {
            $this->refreshCompraValues($compra_id);
        }
        
    }

    public function editQuantity($compra_id, $num) {
        try {
            $compra = $this->loadCompra($compra_id);

            if (!is_numeric($num)) {
                throw new Exception("Quantidade de itens inválida, o valor deve ser numérico.");
            }

            if ($compra['quantidade'] == $num) {
                return;
            }

            $new_unitary_value = $compra['valor_total'] / $num;
            $this->conn->prepareupdate(array($num, $new_unitary_value), array("quantidade", "valor_unitario"), "compra", $compra['id'], "id");
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível alterar a quantidade de itens da compra.");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());            
        }
    }

    public function refreshCompraValues($compra_id) {
        try {
            $compra = $this->loadCompra($compra_id);
            $total_value = 0;
            foreach ($compra['parcelas'] as $index => $parcela) {
                $total_value += $parcela['valor'];
            }
            $unitary_value = $total_value / $compra['quantidade'];
            $this->conn->prepareupdate($unitary_value, valor_unitario, "compra", $compra['id'], "id");
            if (!$this->conn->executa()) {
                throw new Exception("Não foi possível alterar a quantidade de itens da compra.");
            }
        } catch (Exception $ex) {
            throw new Exception("Não foi possível atualizar os valores da compra.");
        }
    }

    public function listCompras($evento_id, $categoria_id = null) {


        $financemodel = new financeModel($this->conn);
        $compras_query = $financemodel->listCompras($evento_id, $categoria_id);


        foreach ($compras_query as $index => $compra) {
            $compras_query[$index]['valor_unitario'] = number_format($compra['valor_unitario'], 2, ",", ".");
            $compras_query[$index]['valor_total'] = number_format($compra['valor_total'], 2, ",", ".");
        }

        $compras = array();
        if (is_null($id_categoria) || !is_numeric($id_categoria)) {
            foreach ($compras_query as $index => $compra) {
                $compras[$compra["nome_categoria"]]["itens"][] = $compra;
                $compras[$compra["nome_categoria"]]["id"] = $compra["id_categoria"];
                $compras[$compra["nome_categoria"]]["valor_total"]+= str_replace(",", ".", str_replace(".", "", $compra["valor_total"]));
            }
        } else {
            $compras[$compras_query[0]["nome_categoria"]]["itens"] = $compras_query;
            $compras[$compras_query[0]["nome_categoria"]]["id"] = $categoria_id;
        }

        foreach ($compras as $index => $compra) {
            $compras[$index]['valor_total'] = number_format($compra['valor_total'], 2, ',', '.');
        }

        return $compras;
    }

    private function loadCompra($compra_id) {
        if (!is_numeric($compra_id)) {
            throw new Exception("Identificador da compra inválido.");
        }

        $fields = Array("id", "nome", "id_evento", "tipo", "valor_unitario", "quantidade", "status", "data_criacao");
        $this->conn->prepareselect("compra", $fields, "id", $compra_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Nenhuma compra encontrada.");
        }

        $compra = $this->conn->fetch;
        $compra['valor_unitario_str'] = "R$" . number_format($compra['valor_unitario'], 2, ',', '.');

        $fields_parcela = Array("id", "id_compra", "valor", "status", "data_vencimento", "data_pagamento", "id_comprovante", "tipo_comprovante");
        $this->conn->prepareselect("parcela_compra", $fields_parcela, "id_compra", $compra_id, "", "", "", PDO::FETCH_ASSOC, "all");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível localizar as parcelas dessa compra.");
        }
        $compra['valor_total'] = $compra['quantidade'] * $compra['valor_unitario'];
        $compra['valor_total_str'] = "R$" . number_format($compra['valor_total'], 2, ',', '.');

        $compra['parcelas'] = $this->conn->fetch;
        foreach ($compra['parcelas'] as $index => $parcela) {
            $compra['parcelas'][$index]['valor_str'] = "R$" . number_format($parcela['valor'], 2, ',', '.');
        }
        return $compra;
    }

    public function getCategorias() {

        $this->conn->prepareselect("categoria_compra", array("id", "nome"), "", "", "", "", "", PDO::FETCH_ASSOC, "all");
        if (!$this->conn->executa()) {
            throw new Exception("Nenhuma categoria encontrada.");
        }

        return $this->conn->fetch;
    }

    private function checkCategoria($categoria, $type = "id") {

        if (!is_numeric($categoria) && $type == "id") {
            throw new Exception("Identificador da categoria em formato inválido.");
        }

        if ($type != "id" && $type != "nome") {
            throw new Exception("O tipo de campo é inválido para checar categorias.");
        }

        $this->conn->prepareselect("categoria_compra", "id", $type, $categoria);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            return false;
        }

        return true;
    }

    private function validateParcela($parcela) {
        if (!is_numeric($parcela['valor'])) {
            throw new Exception("Valor da parcela inválido. ");
        }

        if ($parcela['status'] < 0 || $parcela['status'] > 2) {
            throw new Exception("Status da parcela em formato inválido.");
        }
    }

    private function validateCompra($compra) {
        if (!is_array($compra)) {
            throw new Exception("O item de compra está em formato inválido.");
        }
        if (is_null($compra['nome']) || $compra['nome'] == "") {
            throw new Exception("O nome da compra é inválido.");
        }

        if (!is_numeric($compra['quantidade']) || !is_numeric($compra['valor_unitario'])) {
            throw new Exception("A quantidade e o valor unitário precisam ser numéricos.");
        }
        if ($compra['tipo'] != 0 && $compra['tipo'] != 1) {
            throw new Exception("O tipo da compra informado é inválido.");
        }
    }

    public function addCategoria($nome) {

        $nome = trim($nome);
        if ($nome == "") {
            throw new Exception("Digite um nome válido para a categoria.");
        }

        if ($this->checkCategoria($nome, "nome")) {
            throw new Exception("Você não pode adicionar duas categorias com o mesmo nome.");
        }

        $this->conn->prepareinsert("categoria_compra", $nome, 'nome', "STR");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível adicionar a categoria.");
        }
    }

    public function editCompraStatus($compra_id, $status) {
        if ($status != DYON_COMPRAS_STATUS_APROVADO && $status != DYON_COMPRAS_STATUS_CANCELADO && $status != DYON_COMPRAS_STATUS_PENDENTE && $status != DYON_COMPRAS_STATUS_QUITADO) {
            throw new Exception("Status da compra inválido.");
        }

        if (!is_numeric($compra_id)) {
            throw new Exception("O identificador da compra é inválido.");
        }

        $this->conn->prepareupdate($status, "status", "compra", $compra_id, "id", "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o status da compra.");
        }
    }

    public function editCompraType($compra_id) {
        if (!is_numeric($compra_id)) {
            throw new Exception("O identificador da compra é inválido.");
        }

        $this->conn->prepareselect("compra", "tipo", "id", $compra_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Não foi possível localizar a compra.");
        }

        $compra = $this->conn->fetch;

        if ($compra['tipo'] == DYON_COMPRAS_TIPO_ORCAMENTO) {
            $new_type = DYON_COMPRAS_TIPO_COMPRA;
            $tipo_string = "Compra";
        } else if ($compra['tipo'] == DYON_COMPRAS_TIPO_COMPRA) {
            $new_type = DYON_COMPRAS_TIPO_ORCAMENTO;
            $tipo_string = "Orçamento";
        } else {
            throw new Exception("Tipo da compra inválido.");
        }

        $this->conn->prepareupdate($new_type, "tipo", "compra", $compra_id, "id", "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o status da compra.");
        }

        return $tipo_string;
    }

    public function editParcela($parcela_id, $vencimento, $valor) {
        if (!is_numeric($parcela_id)) {
            throw new Exception("O identificador da compra é inválido.");
        }

        $parcela = $this->loadParcelaInfo($parcela_id);
        $fields = array();
        $values = array();
        $bind = array();

        if ($vencimento != $parcela['data_vencimento']) {
            $vencimento = explode("/", $vencimento);
            if (!checkdate($vencimento[1], $vencimento[0], $vencimento[2])) {
                throw new Exception("Data de vencimento inválida.");
            }
            $vencimento_sql = $vencimento[2] . "-" . $vencimento[1] . "-" . $vencimento[0];
            $fields[] = "data_vencimento";
            $values[] = $vencimento_sql;
            $bind[] = "STR";
        }

        if ($parcela['valor'] != $valor && is_numeric($valor)) {
            $fields[] = "valor";
            $values[] = $valor;
            $bind[] = "STR";
            $new_value = true;
        }

        $this->conn->prepareupdate($values, $fields, "parcela_compra", $parcela_id, "id", $bind);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível alterar o status da parcela.");
        }

        if ($new_value == true) {
            $this->refreshCompraValues($parcela['id_compra']);
        }
    }

    public function loadParcelaInfo($parcela_id) {
        if (!is_numeric($parcela_id)) {
            throw new Exception("O identificador da compra é inválido.");
        }

        $fields = array("id", "data_vencimento", "data_pagamento", "valor", "status", "id_compra", "id_comprovante", "tipo_comprovante");
        $this->conn->prepareselect("parcela_compra", $fields, "id", $parcela_id);
        if (!$this->conn->executa() || $this->conn->rowcount != 1) {
            throw new Exception("Não foi possível alterar o status da parcela.");
        }

        $parcela = $this->conn->fetch;
        $datetime = new DateTime($parcela['data_vencimento']);
        $parcela['data_vencimento_str'] = $datetime->format("d/m/Y");
        if ($parcela['data_pagamento']) {
            $datetime = new DateTime($parcela['data_pagamento']);
            $parcela['data_pagamento_str'] = $datetime->format("d/m/Y");
        }
        return $parcela;
    }

    public function parcelaConfirm($parcela_id, $comprovante, $tipo_comprovante = 1) {
        $usercontroller = new userController();
        $user = $usercontroller->getUser(DYON_USER_ADMIN);
        $user_info = $user->getBasicInfo();

        if (!is_numeric($parcela_id)) {
            throw new Exception("Identificado da parcela inválido.");
        }

        $fields = Array("id", "id_compra", "data_vencimento", "valor", "status", "id_comprovante");
        $this->conn->prepareselect("parcela_compra", $fields, "id", $parcela_id);
        if (!$this->conn->executa() || $this->conn->rowcount == 0) {
            throw new Exception("Nenhuma parcela encontrada com essas informações.");
        }

        $parcela = $this->conn->fetch;
        if ($parcela['status'] == 0) {
            throw new Exception("Você não pode alterar uma parcela cancelada.");
        } else if ($parcela['status'] == 2) {
            throw new Exception("A parcela já está paga.");
        }

        $datetime = new DateTime();
        $update_fields = Array("status", "data_pagamento");
        $update_values = Array(2, $datetime->format("Y-m-d H:i:s"));
        $update_bind = Array("INT", "STR");
        /* Separar as verificações abaixo em outra função */
        if (is_null($parcela['id_comprovante'])) {
            if ($tipo_comprovante != 2 && $tipo_comprovante != 1) {
                throw new Exception("O tipo de comprovante informado é falso.");
            }

            if ($tipo_comprovante == 1) {
                if (!is_file($comprovante['tmp_name']) || ($comprovante['type'] != "image/jpeg" && $comprovante['type'] != "image/jpg" && $comprovante['type'] != "image/png")) {
                    throw new Exception("Comprovante inválido.");
                }
                if (!preg_match("`^[-0-9A-Z_\.]+$`i", basename($comprovante['tmp_name'])) || strlen(basename($comprovante['tmp_name'])) > 100) {
                    throw new Exception("Nome do arquivo inválido ou muito grande.");
                }
                switch ($comprovante['type']) {
                    case "image/jpeg":
                    case "image/jpg":
                        $end = "jpg";
                        break;
                    case "image/png":
                        $end = "png";
                        break;
                }
                $comprovante_name = uniqid($parcela['id'] . "_") . "." . $end;
                if (!move_uploaded_file($comprovante['tmp_name'], "./comprovante/compra/" . $comprovante_name)) {
                    throw new Exception("Não foi possível carregar o comprovante.");
                }
                $update_fields[] = "id_comprovante";
                $update_values[] = $comprovante_name;
                $update_fields[] = "tipo_comprovante";
                $update_values[] = 1;
                $update_bind[] = "STR";
                $update_bind[] = "INT";
            } else if ($tipo_comprovante == 2) {
                if (!is_string($comprovante) || strlen($comprovante) > 100 || $comprovante == "") {
                    throw new Exception("Código da transação inválido.");
                }
                $update_fields[] = "id_comprovante";
                $update_values[] = $comprovante;
                $update_fields[] = "tipo_comprovante";
                $update_values[] = 2;
                $update_bind[] = "STR";
                $update_bind[] = "INT";
            }
        }


        $this->conn->prepareupdate($update_values, $update_fields, "parcela_compra", $parcela['id'], "id", $update_bind);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível confirmar a parcela.");
        }

        $parcela['data_pagamento'] = $datetime->format("d/m/Y");

        return $parcela;
    }

    public function parcelaCancel($parcela_id) {
        $usercontroller = new userController();
        $user = $usercontroller->getUser(DYON_USER_ADMIN);

        if (!is_numeric($parcela_id)) {
            throw new Exception("Identificado da parcela inválido.");
        }

        $this->conn->prepareupdate(0, "status", "parcela_compra", $parcela_id, "id", "INT");
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível cancelar a parcela.");
        }
    }

    public function loadCompraInterface($compra_id, $twig, $usercontroller) {
        try {
            $compra = $this->loadCompra($compra_id);
            $user = $usercontroller->getUser(DYON_USER_ADMIN);
            $user->getAdminInfo();
            $eventcontroller = new eventController();
            $events_select = $eventcontroller->listEvents(array(), true);
            echo $twig->render("financeiro/compra_finances.twig", Array("user" => $user->getBasicInfo(), "compra" => $compra, "events_select" => $events_select, "config" => config::$html_preload));
        } catch (Exception $error) {
            echo $twig->render("financeiro/error_finances.twig", Array("finances__error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));
        }
    }

}
