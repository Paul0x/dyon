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

class flowModel {
    
    private $conn;
    
    public function __construct(conn $conn) {
        $this->conn = $conn;
    }
    
    public function getReceitaAnterior(Datetime $datetime, $evento) {        
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote g ON b.id_lote = g.id "
                . "WHERE b.status IN(2,3) AND a.status = 2 AND a.data_pagamento < '".$date_query." 23:59:59' AND g.id_evento = $evento";
        $result['p'] = $this->conn->freeQuery($query);
        $result['p'] = $result['p'][0];
                
        $query = "SELECT sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote g ON b.id_lote = g.id "
                . "WHERE b.status IN(2,3) AND a.status = 1 AND a.data_vencimento < '".$date_query." 23:59:59' AND g.id_evento = $evento";
        $result['np'] = $this->conn->freeQuery($query);
        $result['np'] = $result['np'][0];
        
        $result['t'] = $result['p'] + $result['np'];  
        return $result;        
    }
    
    public function getDespesaAnterior(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_compra a "
                . "INNER JOIN compra b ON a.id_compra = b.id "
                . "WHERE b.status = 2 AND b.tipo = 0 AND a.data_vencimento < '".$date_query." 23:59:59' AND b.id_evento = $evento";
        $result['p'] = $this->conn->freeQuery($query);
        $result['t'] = $result['p'][0];    
        return $result;        
    }
    
    public function getReceitaDia(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 2 AND l.id_evento = $evento AND a.data_pagamento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['p'] = $result['p'][0];
                
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 1 AND l.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['np'] = $this->conn->freeQuery($query);
        $result['np'] = $result['np'][0];
        
        $result['t'] = $result['p'] + $result['np'];        
        return $result;        
    }
    
    public function getDespesaDia(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_compra a "
                . "INNER JOIN compra b ON a.id_compra = b.id "
                . "WHERE b.status = 2 AND b.tipo = 0 AND b.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['t'] = $result['p'][0]; 
        return $result;        
    }
    
    
    public function getReceitaSemana(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("-7 Days");
        $date_start = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 2 AND l.id_evento = $evento AND a.data_pagamento BETWEEN '".$date_start." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['p'] = $result['p'][0];
                
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 1 AND l.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_start." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['np'] = $this->conn->freeQuery($query);
        $result['np'] = $result['np'][0];
        
        $result['t'] = $result['p'] + $result['np'];        
        return $result;        
    }
    
    public function getDespesaSemana(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("-7 Days");
        $date_start = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_compra a "
                . "INNER JOIN compra b ON a.id_compra = b.id "
                . "WHERE b.status = 2 AND b.tipo = 0 AND b.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_start." 00:00:00' AND '".$date_query." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['t'] = $result['p'][0]; 
        return $result;        
    }
    
    public function getReceitaMes(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("last day of this month");
        $date_query_fim = $datetime->format("Y-m-d");
        $datetime->modify("first day of this month");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 2 AND l.id_evento = $evento AND a.data_pagamento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['p'] = $result['p'][0];
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote b ON a.id_pacote = b.id "
                . "INNER JOIN lote l ON b.id_lote = l.id "
                . "WHERE b.status IN(2,3) AND a.status = 1 AND l.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59'";
        $result['np'] = $this->conn->freeQuery($query);
        $result['np'] = $result['np'][0];
        
        $result['t'] = $result['p'] + $result['np'];        
        return $result;        
    }
    
    public function getDespesaMes(Datetime $datetime, $evento) {
        if(!is_numeric($evento)) {
            return;
        }
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("last day of this month");
        $date_query_fim = $datetime->format("Y-m-d");
        $datetime->modify("first day of this month");
        $query = "SELECT "
                . "sum(a.valor) "
                . "FROM parcela_compra a "
                . "INNER JOIN compra b ON a.id_compra = b.id "
                . "WHERE b.status = 2 AND b.tipo = 0 AND b.id_evento = $evento AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59'";
        $result['p'] = $this->conn->freeQuery($query);
        $result['t'] = $result['p'][0]; 
        return $result;        
    }
    
    public function getFlowMonthDescReceita(Datetime $datetime, $evento) {
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("last day of this month");
        $date_query_fim = $datetime->format("Y-m-d");
        $datetime->modify("first day of this month");
        $query = "SELECT "
                . "u.nome, u.email, u.id as 'id_usuario', p.tipo_pagamento, DATE_FORMAT(a.data_pagamento,'%d/%m/%Y') as 'data_pagamento', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote p ON a.id_pacote = p.id "
                . "INNER JOIN usuario u ON u.id = p.id_usuario "
                . "WHERE p.status IN(2,3) AND a.status = 2 AND a.data_pagamento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59' "
                . "ORDER BY a.data_pagamento";
        $result['p']['t'] = $this->conn->freeQuery($query, true);
        $query = "SELECT "
                . "u.nome, u.email, u.id as 'id_usuario',  p.tipo_pagamento, DATE_FORMAT(a.data_pagamento,'%d/%m/%Y') as 'data_pagamento', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote p ON a.id_pacote = p.id "
                . "INNER JOIN usuario u ON u.id = p.id_usuario "
                . "WHERE p.status IN(2,3) AND a.status = 1 AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59' "
                . "ORDER BY a.data_vencimento";
        $result['np']['t'] = $this->conn->freeQuery($query, true);
        return $result;        
    }
    
    public function getFlowMonthDescDespesa(Datetime $datetime, $evento) {
        $date_query = $datetime->format("Y-m-d");
        $datetime->modify("last day of this month");
        $date_query_fim = $datetime->format("Y-m-d");
        $datetime->modify("first day of this month");
        $query = "SELECT "
                . "c.nome, c.id as 'id_compra', t.nome as 'categoria', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor "
                . "FROM parcela_compra a "
                . "INNER JOIN compra c ON a.id_compra = c.id "
                . "INNER JOIN categoria_compra t ON c.id_categoria = t.id "
                . "WHERE c.status = 2 AND c.tipo = 0 AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query_fim." 23:59:59' "
                . "ORDER BY a.data_vencimento";
        $result['p']['t'] = $this->conn->freeQuery($query, true);
        return $result;        
    }
    
      
    public function getFlowDayDescReceita(Datetime $datetime, $evento) {        
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "u.nome, u.email, u.id as 'id_usuario', p.tipo_pagamento, DATE_FORMAT(a.data_pagamento,'%d/%m/%Y') as 'data_pagamento', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor"
                . " FROM parcela_pacote a "
                . "INNER JOIN pacote p ON a.id_pacote = p.id "
                . "INNER JOIN usuario u ON u.id = p.id_usuario"
                . " WHERE p.status IN(2,3) AND a.status = 2 AND a.data_pagamento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59' "
                . "ORDER BY a.data_pagamento";
        $result['p']['t'] = $this->conn->freeQuery($query, true);
        $query = "SELECT "
                . "u.nome, u.email, u.id as 'id_usuario',  p.tipo_pagamento, DATE_FORMAT(a.data_pagamento,'%d/%m/%Y') as 'data_pagamento', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor "
                . "FROM parcela_pacote a "
                . "INNER JOIN pacote p ON a.id_pacote = p.id "
                . "INNER JOIN usuario u ON u.id = p.id_usuario "
                . "WHERE p.status IN(2,3) AND a.status = 1 AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59' "
                . "ORDER BY a.data_vencimento";
        $result['np']['t'] = $this->conn->freeQuery($query, true);
        return $result;        
    }
    
    public function getFlowDayDescDespesa(Datetime $datetime, $evento) {        
        $date_query = $datetime->format("Y-m-d");
        $query = "SELECT "
                . "c.nome, c.id as 'id_compra', t.nome as 'categoria', DATE_FORMAT(a.data_vencimento,'%d/%m/%Y') as 'data_vencimento', a.status, a.valor "
                . "FROM parcela_compra a "
                . "INNER JOIN compra c ON a.id_compra = c.id "
                . "INNER JOIN categoria_compra t ON c.id_categoria = t.id "
                . "WHERE c.status = 2 AND c.tipo = 0 AND a.data_vencimento BETWEEN '".$date_query." 00:00:00' AND '".$date_query." 23:59:59' "
                . "ORDER BY a.data_vencimento";
        $result['p']['t'] = $this->conn->freeQuery($query, true);
        return $result;        
    }
    
    
}
