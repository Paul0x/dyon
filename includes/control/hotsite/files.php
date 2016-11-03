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
 *  File: files.php
 *  Type: Library
 *  =====================================================================
 * 
 */
define(FILES_URL, $_SERVER['DOCUMENT_ROOT']."dyon");
class hotsiteFiles {
    
    public function saveBackgroundImage($hotsite, $background_image) {
        if (!$hotsite->getId()) {
            throw new Exception("Identificador do hotsite inválido.");
        }

        if (!is_file($background_image['tmp_name']) || ($background_image['type'] != "image/jpeg" && $background_image['type'] != "image/jpg" && $background_image['type'] != "image/png")) {
            throw new Exception("Imagem invalida.");
        }
        if (!preg_match("`^[-0-9A-Z_\.]+$`i", basename($background_image['tmp_name'])) || strlen(basename($background_image['tmp_name'])) > 100) {
            throw new Exception("Nome do arquivo inválido ou muito grande.");
        }
        switch ($background_image['type']) {
            case "image/jpeg":
            case "image/jpg":
                $end = "jpg";
                break;
            case "image/png":
                $end = "png";
                break;
        }
        $background_image_name = uniqid($hotsite->getId() . "_") . "." . $end;
        if (!move_uploaded_file($background_image['tmp_name'], FILES_URL."/hotsite/background_image/" . $background_image_name)) {
            throw new Exception("Não foi possível carregar a imagem.");
        }
        
        return $background_image_name;
    }
    
    public function removeBackgroundImage($hotsite, $background_image_filename) {        
        if (!$hotsite->getId()) {
            throw new Exception("Identificador do hotsite inválido.");
        }
        
        if(!$background_image_filename) {
            return;
        }
        
        $sufix = substr($background_image_filename, strlen($background_image_filename)-3, 3);
        if($sufix != "png" && $sufix != "jpg") {
            throw new Exception("O arquivo de background não pode ser removido.");
        } 
        
        if(file_exists(FILES_URL."/hotsite/background_image/" . $background_image_filename)) {
            unlink(FILES_URL."/hotsite/background_image/" . $background_image_filename);
        } else {
            throw new Exception("O arquivo de background não existe. - ".FILES_URL."/hotsite/background_image/" . $background_image_filename);
        }
    }
}

class blockFiles {
    
    public function saveBackgroundImage($block, $background_image) {
        if (!$block->getId()) {
            throw new Exception("Identificador do hotsite inválido.");
        }

        if (!is_file($background_image['tmp_name']) || ($background_image['type'] != "image/jpeg" && $background_image['type'] != "image/jpg" && $background_image['type'] != "image/png")) {
            throw new Exception("Imagem invalida.");
        }
        if (!preg_match("`^[-0-9A-Z_\.]+$`i", basename($background_image['tmp_name'])) || strlen(basename($background_image['tmp_name'])) > 100) {
            throw new Exception("Nome do arquivo inválido ou muito grande.");
        }
        switch ($background_image['type']) {
            case "image/jpeg":
            case "image/jpg":
                $end = "jpg";
                break;
            case "image/png":
                $end = "png";
                break;
        }
        $background_image_name = uniqid($block->getId() . "_") . "." . $end;
        if (!move_uploaded_file($background_image['tmp_name'], FILES_URL."/hotsite/block_image/" . $background_image_name)) {
            throw new Exception("Não foi possível carregar a imagem.");
        }
        
        return $background_image_name;
    }
    
    public function removeBackgroundImage($block, $background_image_filename) {        
        if (!$block->getId()) {
            throw new Exception("Identificador do hotsite inválido.");
        }
        
        if(!$background_image_filename) {
            return;
        }
        
        $sufix = substr($background_image_filename, strlen($background_image_filename)-3, 3);
        if($sufix != "png" && $sufix != "jpg") {
            throw new Exception("O arquivo de background não pode ser removido.");
        } 
        
        if(file_exists(FILES_URL."/hotsite/block_image/" . $background_image_filename)) {
            unlink(FILES_URL."/hotsite/block_image/" . $background_image_filename);
        } else {
            throw new Exception("O arquivo de background não existe. - ".FILES_URL."/hotsite/block_image/" . $background_image_filename);
        }
    }
}
