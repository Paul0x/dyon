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
 *  File: menu.php
 *  Type: Main Menu Content Controller
 *  =====================================================================
 * 
 */

class menu extends content {
    private $links;
    
    public function getMenuLinks() {
        if(!is_numeric($this->id)) {
            throw new Exception("O identificador do menu não é válido.");
        }
        
        $search_args = array("id_parente","tipo_parente","tipo");
        $search_values = array($this->id,DYON_HOTSITE_CONTENT_MENU,DYON_HOTSITE_CONTENT_MENU_LINK);
        $this->conn->prepareselect("conteudo", "id", $search_args, $search_values, "same", "", "", NULL, "all");
        if(!$this->conn->executa()) {
            throw new Exception("Esse menu não possui nenhum link.");
        }
        
        $links_id = $this->conn->fetch();
        $this->links = array();
        foreach($links_id as $index => $link_id) {
            $this->links[] = new menu_link($link_id);
        }
        
        $this->links = $this->conn->fetch();
    }
    
    public function init($id) {
        if(!is_numeric($id)) {
            throw new Exception("O identificador do menu é inválido.");
        }
        $usercontroller = new userController();
        $user = $usercontroller->getUser();
        $hotsite = unserialize($_SESSION['hotsitecache']);
        
        if(!is_a($hotsite, "hotsite")) {
            throw new Exception("O hotsite não está carregado corretamente.");
        }
        
        $hotsite->checkPermission($user);
    }
    
    
}
