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
 *  File: content.php
 *  Type: Generic Object
 *  =====================================================================
 * 
 */
class content {
    /* Database Connection */

    protected $conn;

    /* Content Basic Structure */
    protected $id;
    protected $type;
    protected $modification_date;
    protected $parent_id;
    protected $parent_type;
    
    public function __construct($id = false) {
        $this->conn = new conn();
        
        if($id && is_numeric($id)) {
            $this->init($id);
        }
    }
    
    public function getId() {
        if(!is_numeric($this->id)) {
            throw new Exception("Identificador do conteúdo não informado.");
        }
        
        return $this->id;
    }
    
    protected function loadFromDb() {
        $fields = Array("id","id_parente","data_alteracao","tipo","info","tipo_parente");
        
        if(!is_numeric($this->id) || !is_numeric($this->type)) {
            throw new Exception("Informações inválidas para buscar o conteúdo.");
        }
        
        $this->conn->prepareselect("conteudo", $fields, array("id","tipo"), array($this->id,$this->type));
        if(!$this->conn->executa()) {
            throw new Exception("Conteúdo não encontrado.");
        } 
        
        return $this->conn->fetch;
    }
    
    public function render() {
        return "aylamao";        
    }
}
