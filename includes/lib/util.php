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
 *  File: util.php
 *  Type: Library
 *  =====================================================================
 * 
 */
class util {
    
    
     /**
     * Verifica se os campos do array estão preenchidos.
     * @param Array $array - O array contendo os valores.
     * @param Array $required_fields - Os índices do array que devem estar preenchidos.
     * @param String $table - Caso exista, o nome da tabela que servirá de sulfixo ou prefixo.
     * @param Boolean $prefix - Verifica se a tabela serve como prefixo. Por padrão ela será inserida como sulfixo.
     */
    static function checkRequired($array, $required_fields, $table = "", $prefix = false) {
        if(!is_array($array)) {
            throw new Exception("O array de valores é inválido.");
        }
        
        /* Corrige os campos requisitados, caso seja necessário adaptar uma tabela.*/
        if($table != "") {
            if($prefix == false) {
                foreach($required_fields as $index => $value) {
                    $required_fields[$index] = $value."_".$table;                    
                }
            }
            else {
                foreach($required_fields as $index => $value) {
                    $required_fields[$index] = $table."_".$value;
                }
            }           
        }
        
        foreach($required_fields as $value) {
            $array_value = trim($array["value"]);
            if(!isset($array["value"]) || is_null($array_value)) {
                return false;
            }
        }
        
        return true;          
    }
    
    static function getGLOBAL($index,$filter,$type)
    {
        return filter_input($type,$index,$filter);
    }
    
    static function getPOST($index,$filter = FILTER_SANITIZE_SPECIAL_CHARS)
    {
        if(isset($_POST[$index])) {
            return util::getGLOBAL($index,$filter,INPUT_POST);
        }
        else {
            return "";
        }
    }
    
    static function getGET($index,$filter = FILTER_SANITIZE_SPECIAL_CHARS)
    {
        if(isset($_POST[$index])) {
            return util::getGLOBAL($index,$filter,INPUT_GET);
        }
        else {
            return "";
        }
    }
   
}