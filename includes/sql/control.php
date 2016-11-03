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



class controlModel {
    
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function listGroupsQuery($event_id) {
        
        if(!is_numeric($event_id)) {
            throw new Exception("O identificador do evento é inválido.");
        }
        $query = "SELECT a.id, a.nome as 'nome_grupo', b.nome as 'nome_usuario', rg FROM grupo a INNER JOIN usuario b ON a.id_lider = b.id WHERE id_evento = $event_id GROUP BY a.id";
        $grupos = $this->conn->freeQuery($query,true,true,PDO::FETCH_NUM);
        if(is_null($grupos[0][0])) {
            throw new Exception("Nenhum grupo disponível.");
        }
        return $grupos; 
    }
    
}
