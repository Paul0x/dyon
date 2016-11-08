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

class financeModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function loadSummary($id_event) {
        $date = new DateTime();
        if (!is_numeric($id_event)) {
            throw new Exception("Identificador do evento é inválido.");
        }

        $summary = Array();
        $summary["pacotes"] = $this->getReceita($id_event);
        $summary["compras"] = $this->getDespesa($id_event, $date);
        /**
         *  5 Últimas Parcelas Pagas
         */
        $query = "SELECT p.valor, u.nome, DATE_FORMAT(p.data_pagamento,'%d/%m/%Y') as 'data_pagamento' FROM parcela_pacote p INNER JOIN pacote a ON a.id = p.id_pacote INNER JOIN usuario u ON u.id = a.id_usuario WHERE a.status IN (2,3) AND p.status = 2 ORDER BY p.data_pagamento DESC LIMIT 5";
        $summary["list"]["parcelas"] = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);

        /**
         *  10 Últimas Parcelas Vencidas [ Compras ]
         */
        $query = "SELECT p.valor, c.nome, DATE_FORMAT(p.data_vencimento,'%d/%m/%Y') as 'data_pagamento' FROM parcela_compra p INNER JOIN compra c ON c.id = p.id_compra WHERE c.status = 2 AND c.tipo = 0 AND p.data_vencimento < '" . $date->format("Y-m-d") . "' ORDER BY p.data_vencimento DESC LIMIT 10";
        $summary["list"]["compras_vencida"] = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);


        /**
         *  10 Últimas Parcelas Vencidas [ Compras ]
         */
        $query = "SELECT p.valor, c.nome, DATE_FORMAT(p.data_vencimento,'%d/%m/%Y') as 'data_pagamento' FROM parcela_compra p INNER JOIN compra c ON c.id = p.id_compra WHERE c.status = 2 AND c.tipo = 0 AND p.data_vencimento >= '" . $date->format("Y-m-d") . "' ORDER BY p.data_vencimento ASC LIMIT 10";
        $summary["list"]["compras_proximas"] = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);

        return $summary;
    }

    public function listCompras($evento_id, $categoria_id = null, $order = null) {

        if (!is_numeric($evento_id)) {
            throw new Exception("Identificador do evento inválido.");
        }

        $fields = "a.id,"
                . " a.nome,"
                . " a.status,"
                . " a.tipo,"
                . " a.id_categoria,"
                . " b.nome as 'nome_categoria',"
                . " sum(c.valor) as 'valor_total',"
                . " a.quantidade,"
                . " a.valor_unitario,"
                . " count(c.id) as 'num_parcelas'";
        $query = "SELECT $fields FROM compra a "
                . "INNER JOIN categoria_compra b on a.id_categoria = b.id "
                . "INNER JOIN parcela_compra c on c.id_compra = a.id "
                . "WHERE a.id_evento = $evento_id";

        if ($categoria_id != null && is_numeric($categoria_id)) {
            $query.= " AND b.id = $categoria_id";
        }
        $query.= " GROUP BY a.id";

        if ($order == "status") {
            $query.= " ORDER BY a.status";
        }
        if ($order == "vencimento") {
            $query.= " ORDER BY c.data_vencimento";
        }

        $compras = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);
        if (is_null($compras[0][0])) {
            throw new Exception("Compras não encontradas.");
        }

        return $compras;
    }

    public function getReceita($id_event) {
        /**
         *  Busca dos Pacotes e Parcelas
         */
        if (!is_numeric($id_event)) {
            throw new Exception("Identificador do evento inválido.");
        }

        $query = "SELECT count(DISTINCT a.id) as 'num_pacotes', a.status as 'status_pacote', sum(c.valor) as 'valor_total' FROM pacote a "
                . "INNER JOIN lote b ON a.id_lote = b.id "
                . "INNER JOIN parcela_pacote c ON c.id_pacote = a.id "
                . "WHERE id_evento = $id_event AND c.status != 0 ";
        $query .= "GROUP BY a.status ORDER BY a.status DESC";
        $receitas = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);

        if (!is_array($receitas)) {
            throw new Exception("Não foi possível contabilizar as vendas.");
        }

        foreach ($receitas as $index => $pacotes) {

            $query = "SELECT count(a.id) as 'num_parcelas', sum(a.valor) as 'valor_parcelas', a.status as 'status_parcela' FROM parcela_pacote a "
                    . "INNER JOIN pacote b ON a.id_pacote = b.id "
                    . "INNER JOIN lote c ON b.id_lote = c.id "
                    . "WHERE c.id_evento = $id_event AND b.status = " . $pacotes["status_pacote"]." ";
            $query .= " GROUP BY a.status";
            $receitas[$index]["parcelas"] = $this->conn->freeQuery($query, true, PDO::FETCH_ASSOC);
        }

        return $receitas;
    }
    
    public function getDespesa($id_event, $date) {
        
        /**
         *  Busca das Compras
         */
        $query = "SELECT sum(p.valor) FROM parcela_compra p INNER JOIN compra c ON p.id_compra = c.id WHERE c.status = 2 AND p.data_vencimento < '" . $date->format("Y-m-d") . "' AND c.id_evento = $id_event AND c.tipo = 0";
        $compras['total_vencido'] = $this->conn->freeQuery($query);
        $compras['total_vencido'] = $compras["total_vencido"][0];
        $query = "SELECT sum(p.valor) FROM parcela_compra p INNER JOIN compra c ON p.id_compra = c.id WHERE c.status = 2 AND c.id_evento = $id_event AND c.tipo = 0";
        $compras['total_planejado'] = $this->conn->freeQuery($query);
        $compras['total_planejado'] = $compras["total_planejado"][0];
        
        return $compras;

    }
    
}
