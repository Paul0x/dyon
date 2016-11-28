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
 */


define("DYON_PACOTEMODEL_PACOTESBYQUERY", 20);

class pacoteModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function listPacotesQuery($filters) {

        /**
         *  Lista de campos
         *  a = Tabela Pacote
         *  b = Tabela Grupo
         *  c = Tabela Parcela Pacote
         *  d = Tabela Usuário
         */
        if (!is_numeric($filters['id_evento'])) {
            throw new Exception("ID do evento inválida.");
        }

        $available_filters = Array("nome", "grupo", "rg", "cidade", "estado");
        $field_list = "g.nome as 'nome_evento', d.nome as 'nome_usuario', b.nome as 'nome_grupo', e.nome as 'lote', tipo_pagamento, a.status as 'status_pacote', SUM(c.valor) as 'valor_total', a.id, d.id";
        $query = "SELECT " . $field_list . " FROM pacote a " .
                "INNER JOIN grupo b ON a.id_grupo = b.id " .
                "INNER JOIN parcela_pacote c ON a.id = c.id_pacote " .
                "INNER JOIN usuario d ON a.id_usuario = d.id " .
                "INNER JOIN lote e ON a.id_lote = e.id "
                . " INNER JOIN evento g ON e.id_evento = g.id ";

        switch ($filters['field']['label']) {
            case "grupo":
                $filters['field']['label'] = "b.nome";
                break;
            case "nome":
                $filters['field']['label'] = "d.nome";
                break;
        }

        $where = "WHERE ";
        $where.= $filters['field']['label'] . " like '%" . $this->conn->escapeString($filters['field']['value']) . "%'";
        $where.= " AND e.id_evento = " . $filters['id_evento'];
        $where.= " AND c.status != 0";

        if ($filters['status'] != null AND is_array($filters['status'])) {
            $where.= " AND a.status IN (";
            foreach ($filters['status'] as $index => $status) {
                if (!is_numeric($status)) {
                    throw new Exception("Status pesquisado inválido.");
                }
                $where.= $status;
                if ($index + 1 != count($filters['status'])) {
                    $where.= ",";
                }
            }
            $where.= ")";
        }

        if ($filters['pagamento'] != null AND is_numeric($filters['pagamento'])) {
            $where.= " AND a.tipo_pagamento = " . $filters['pagamento'];
        }

        if ($filters['lote'] != null AND is_numeric($filters['lote'])) {
            $where.= " AND a.id_lote = " . $filters['lote'];
        }

        $query.= $where;
        $query.= " GROUP BY a.id";


        if (!is_array($filters['order']) || count($filters['order']) == 0) {
            $query.= " ORDER BY a.data_alteracao DESC";
        } else {
            $i = 0;
            foreach ($filters['order'] as $index => $order) {
                if ($i == 0) {
                    $query.= " ORDER BY " . $order['field'] . " " . $order['mode'];
                } else {
                    $query.= ", " . $order['field'] . " " . $order['mode'];
                }
                $i++;
            }
        }
        $query.= " LIMIT " . $filters['page'] . "," . DYON_PACOTEMODEL_PACOTESBYQUERY;
        $pacotes['list'] = $this->conn->freeQuery($query, true, true, PDO::FETCH_BOTH);
        if (is_null($pacotes['list'][0][0])) {
            throw new Exception("Nenhum pacote encontrado com os filtros especificados.");
        }

        // Contagem de colunas
        $count_query = "SELECT count(DISTINCT a.id) FROM pacote a " .
                "INNER JOIN grupo b ON a.id_grupo = b.id " .
                "INNER JOIN parcela_pacote c ON a.id = c.id_pacote " .
                "INNER JOIN usuario d ON a.id_usuario = d.id " .
                "INNER JOIN lote e ON a.id_lote = e.id ";
        $count_query.= $where;
        $pacotes['count']['total'] = $this->conn->freeQuery($count_query);
        $pacotes['count']['total'] = $pacotes['count']['total'][0];
        $pacotes['count']['query'] = count($pacotes['list']);
        return $pacotes;
    }

    public function listPacotesByParcela($filters) {
        if (!is_numeric($filters['id_evento'])) {
            throw new Exception("ID do evento inválida.");
        }
        if (!is_numeric($filters['status']) || $filters['status'] < 0 || $filters['status'] > 4) {
            $filters['status'] = 3;
        }

        if (!is_numeric($filters['page'])) {
            $filters['page'] = 0;
        }

        $pacotes['field_list'] = Array("pacote", "cliente", "lote", "valor", "pagamento");
        $field_list = "b.id as 'id_pacote', c.nome as 'nome_usuario', d.nome as 'nome_lote', a.valor as 'valor_parcela', a.data_pagamento as 'data_confirmacao', a.status as 'status_parcela', c.id as 'id_usuario'";
        $query = "SELECT " . $field_list . " FROM parcela_pacote a " .
                "INNER JOIN pacote b ON a.id_pacote = b.id " .
                "INNER JOIN usuario c ON b.id_usuario = c.id " .
                "INNER JOIN lote d ON b.id_lote = d.id ";
        $query.= " WHERE a.status = " . $filters['status'];
        $query.= " GROUP BY a.id";
        $query.= " ORDER BY a.data_pagamento DESC, b.id ASC";
        $query.= " LIMIT " . $filters['page'] . "," . DYON_PACOTEMODEL_PACOTESBYQUERY;
        try {
            $pacotes['list'] = $this->conn->freeQuery($query, true, true, PDO::FETCH_NUM);
        } catch (Exception $error) {
            throw new Exception("Impossível buscar pacotes. :/" . $error->getMessage());
        }
        if (is_null($pacotes['list'][0][0])) {
            throw new Exception("Nenhum pacote encontrado com os filtros especificados.");
        }

        $pacotes['status'] = $filters['status'];
        $pacotes['count']['query'] = count($pacotes['list']);
        return $pacotes;
    }

    public function listPacotesByUser($id_usuario, $status = null, $id_evento = null) {
        /**
         *  Lista de campos
         *  a = Tabela Pacote
         *  b = Tabela Grupo
         *  c = Tabela Parcela Pacote
         *  d = Tabela Evento
         *  e = Tabela Lote
         */
        if (!is_numeric($id_usuario)) {
            throw new Exception("ID do usuário inválido para listar pacotes.");
        }

        $field_list = "d.nome as 'nome_evento', b.nome as 'nome_grupo', e.nome as 'lote', COUNT(c.id) as 'parcelas', tipo_pagamento, a.status as 'status_pacote', SUM(c.valor) as 'valor_total', a.desconto as 'desconto', a.id as 'id_pacote', d.id as 'id_evento', b.codigo_acesso as 'codigo_acesso', b.id as 'id_grupo'";
        $query = "SELECT " . $field_list . " FROM pacote a " .
                "INNER JOIN grupo b ON a.id_grupo = b.id " .
                "INNER JOIN parcela_pacote c ON a.id = c.id_pacote " .
                "INNER JOIN lote e ON a.id_lote = e.id " .
                "INNER JOIN evento d ON e.id_evento = d.id WHERE ";
        $query.= " a.id_usuario = " . $id_usuario . " AND c.status != 0 ";

        if (is_numeric($status)) {
            $query.= " AND a.status = $status ";
        }
        if (is_numeric($id_evento)) {
            $query.= " AND e.id_evento = $id_evento ";
        }

        $query.= "GROUP BY a.id ORDER BY a.data_criacao DESC";
        $pacotes['list'] = $this->conn->freeQuery($query, true, true);

        if (is_null($pacotes['list'][0][0])) {
            throw new Exception("Nenhum pacote encontrado com os filtros especificados.");
        }

        $pacotes['count'] = count($pacotes['list']);
        return $pacotes;
    }

    public function loadPacoteById($id_pacote) {
        /**
         *  Lista de campos
         *  a = Tabela Pacote
         *  b = Tabela Grupo
         *  c = Tabela Parcela Pacote
         *  d = Tabela Evento
         *  e = Tabela Lote
         */
        if (!is_numeric($id_pacote)) {
            throw new Exception("ID do pacote inválido para listar pacotes.");
        }

        $field_list = "d.nome as 'nome_evento', b.nome as 'nome_grupo', a.id_quarto, b.id as 'id_grupo', b.codigo_acesso as 'codigo_acesso', u.nome as 'nome_usuario', a.id_usuario as 'id_usuario', e.id as 'id_lote', e.nome as 'lote', COUNT(c.id) as 'parcelas', tipo_pagamento, a.status as 'status_pacote', SUM(c.valor) as 'valor_total', a.desconto as 'desconto', a.id as 'id_pacote', d.id as 'id_evento', e.pagseguro_hash as 'pagseguro_hash'";
        $query = "SELECT " . $field_list . " FROM pacote a " .
                "INNER JOIN grupo b ON a.id_grupo = b.id " .
                "INNER JOIN parcela_pacote c ON a.id = c.id_pacote  AND c.status IN (1,2)" .
                "INNER JOIN lote e ON a.id_lote = e.id " .
                "INNER JOIN usuario u ON a.id_usuario = u.id " .
                "INNER JOIN evento d ON e.id_evento = d.id WHERE ";
        $query.= " a.id = " . $id_pacote . " GROUP BY a.id";
        $pacote = $this->conn->freeQuery($query, false, true, PDO::FETCH_ASSOC);
        if (is_null($pacote['id_pacote'])) {
            throw new Exception("Nenhum pacote encontrado com os filtros especificados.");
        }

        return $pacote;
    }

    public function getGroupMembers($id_grupo) {
        if (!is_numeric($id_grupo)) {
            throw new Exception("Identificador do grupo inválido. 2");
        }

        $query = "SELECT DISTINCT id_usuario FROM pacote WHERE id_grupo = $id_grupo";
        try {
            $members_id = $this->conn->freeQuery($query, true, true, PDO::FETCH_NUM);
        } catch (Exception $error) {
            throw new Exception("Não foi possível pesquisar o grupo.");
        }

        if (is_null($members_id[0])) {
            throw new Exception("Nenhum integrante encontrado neste grupo.");
        }

        $query_user = "SELECT nome, id FROM usuario WHERE id IN (";

        for ($i = 0; $i < count($members_id) - 1; $i++) {
            $query_user.= $members_id[$i][0] . ",";
        }
        $query_user.= $members_id[count($members_id) - 1][0] . ")";
        try {
            $members = $this->conn->freeQuery($query_user, true, true, PDO::FETCH_ASSOC);
        } catch (Exception $error) {
            throw new Exception("Não foi possível pesquisar o nome dos membros.");
        }
        return $members;
    }

    public function listPacoteByGroups($filters) {

        $query = "SELECT g.id, g.nome, g.codigo_acesso, g.id_lider, g.data_criacao FROM grupo g "
                . "INNER JOIN usuario u ON g.id_lider = u.id "
                . "INNER JOIN pacote p ON g.id = p.id_grupo "
                . "WHERE p.status IN (2,3,4) AND p.id_grupo = g.id ";
        switch ($filters['fieldquery']) {
            case "nome":
                $query.= "AND g.nome  like '%" . $this->conn->escapeString($filters['querystring']) . "%' ";
                break;
            case "codigo":
                $query.= "AND g.codigo_acesso  like '%" . $this->conn->escapeString($filters['querystring']) . "%' ";
                break;
            case "lider":
                $query.= "AND u.nome like '%" . $this->conn->escapeString($filters['querystring']) . "%' ";
        }

        $query.= "GROUP BY g.id ORDER BY g.data_criacao";

        try {
            $groups = $this->conn->freeQuery($query, true, true, PDO::FETCH_ASSOC);
        } catch (Exception $error) {
            throw new Exception("Não foi possível pesquisar os grupos.");
        }

        foreach ($groups as $index => $group) {
            $groups[$index]['members'] = $this->getGroupPacotes($group['id']);
        }

        return $groups;
    }

    public function getGroupPacotes($grupo_id, $status = array(2, 3, 4), $full = false) {
        if (!is_array($status)) {
            throw new Exception("Status dos pacotes inválido.");
        }

        $status_query = "(";
        $status_list = array(0, 1, 2, 3, 4);
        foreach ($status as $index => $value) {
            if (!in_array($value, $status_list)) {
                throw new Exception("Status dos pacotes inválido (2)");
            }
            $status_query.=$value;
            if ($index + 1 != count($status)) {
                $status_query.=",";
            }
        }
        $status_query.= ")";

        if ($full == false) {
            $fields = "p.id_usuario, u.nome, p.id, p.status as 'status_pacote'";
        } else {
            $fields = "p.id_usuario, u.nome, p.id, p.status as 'status_pacote', l.nome as 'lote', SUM(v.valor) as 'valor', p.id_quarto";
        }

        $query_members = "SELECT $fields FROM pacote p "
                . "INNER JOIN lote l ON p.id_lote = l.id "
                . "INNER JOIN parcela_pacote v ON v.id_pacote = p.id AND v.status IN (1,2) "
                . "INNER JOIN usuario u ON p.id_usuario = u.id "
                . "WHERE p.id_grupo = " . $grupo_id . "  AND p.status IN $status_query "
                . "GROUP BY p.id";

        try {
            return $this->conn->freeQuery($query_members, true, true, PDO::FETCH_ASSOC);
        } catch (Exception $error) {
            return "Não foi possível pesquisar os membros do grupo.";
        }
    }

    public function listPacotesByHouse($pacotes) {
        $pacotes_query = "(";
        foreach ($pacotes as $index => $pacote_id) {
            $pacotes_query.= $pacote_id;
            if ($index + 1 != count($pacotes)) {
                $pacotes_query.= ",";
            }
        }
        $pacotes_query .= ")";

        $select_query = "SELECT p.id, c.nome, c.endereco, q.numero, q.id as 'id_quarto' FROM pacote p "
                . "INNER JOIN quarto q ON p.id_quarto = q.id "
                . "INNER JOIN casa c ON q.id_casa = c.id "
                . "WHERE p.id IN $pacotes_query "
                . "GROUP BY q.id ";
        try {
            $rooms = $this->conn->freeQuery($select_query, true, true, PDO::FETCH_ASSOC);
        } catch (Exception $error) {
            throw new Exception("Não foi possível retornar as casas do grupo.");
        }


        if (!is_array($rooms)) {
            throw new Exception("Nenhuma casa encontrada para o grupo informado.");
        }

        foreach ($rooms as $index => $room) {
            $search_query = "SELECT p.id, u.id, u.nome FROM pacote p "
                    . "INNER JOIN usuario u ON p.id_usuario = u.id "
                    . "WHERE p.id_quarto = " . $room['id_quarto'];

            $rooms[$index]['members'] = $this->conn->freeQuery($search_query, true, true, PDO::FETCH_ASSOC);
        }

        return $rooms;
    }

    public function countPacotes($event_id, $status, $datequery = false) {
        if (!is_numeric($event_id)) {
            throw new Exception("O identificador do evento é inválido para busca de pacotes.");
        }

        if ($status < 0 || $status > 4 || !is_numeric($status)) {
            throw new Exception("Status do pacote inválido.");
        }

        $query = "SELECT count(DISTINCT p.id) FROM pacote p INNER JOIN lote l ON l.id = p.id_lote INNER JOIN evento e ON e.id = l.id_evento"
                . " WHERE p.status >= $status AND e.id = $event_id ";

        if (is_array($datequery) && ($datequery['field'] == 'data_criacao' || $datequery['field'] == 'data_aprovacao' || $datequery['field'] == 'data_alteracao')) {
            if ($datequery['datetime_start'] && is_a($datequery['datetime_start'], "DateTime")) {
                $date_start = $datequery['datetime_start']->format("Y-m-d h:i:s");
                $query.= "AND ".$datequery['field']." >= '$date_start' ";
            }
            if ($datequery['datetime_end'] && is_a($datequery['datetime_end'], "DateTime")) {
                $date_end = $datequery['datetime_end']->format("Y-m-d h:i:s");
                $query.= "AND ".$datequery['field']." <= '$date_end' ";
            }
        }
        try {
            $count = $this->conn->freeQuery($query, false, true, PDO::FETCH_NUM);
        } catch (Exception $error) {
            throw new Exception("Não foi possível calcular o número de pacotes.");
        }
        return $count[0];
    }

}
