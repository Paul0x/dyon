<?php
/******************************************
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
 *  File: imagemanager.php
 *  Type: Library
 *  =====================================================================
 * 
 */
class imagem
{

   private $image;
   private $imageresized;
   private $imagecroped;
   private $nome;
   private $tnome;
   private $tx;
   private $ty;
   private $rx;
   private $ry;
   private $cx;
   private $cy;
   private $tmx;
   private $tmy;
   private $tipo;
   private $mime;
   private $size;
   private $filenamecount = 1;
   private $redimensionar = false;
   private $crop = false;
   private $texto = false;
   private $formatos = array("image/jpeg", "image/pjpeg", "image/gif", "image/png");

   /*
    *  Pega Informações da Imagem
    */

   function pegarImagem($imagem)
   {
      /**
       *  Pega uma imagem e extrai informações importantes dela.
       */
      if (!is_array($imagem))
         throw new Exception("A imagem informada não é um array.");
      if ($imagem['name'] == "" || $imagem['size'] == "" || $imagem['tmp_name'] == "" || $imagem['type'] == "")
         throw new Exception("A imagem não possui os atributos necessários.");
      if (!in_array($imagem['type'], $this->formatos))
         throw new Exception("A imagem não está dentro dos formatos.");

      $this->nome = $imagem['name'];
      $this->size = $imagem['size'];
      $this->tnome = $imagem['tmp_name'];
      $this->mime = $imagem['type'];
      switch ($this->mime)
      {
         case "image/jpeg":
            $this->tipo = "jpg";
            $this->image = imagecreatefromjpeg($this->tnome);
            break;
         case "image/pjpeg":
            $this->tipo = "jpeg";
            $this->image = imagecreatefromjpeg($this->tnome);
            break;
         case "image/gif":
            $this->tipo = "gif";
            $this->image = imagecreatefromgif($this->tnome);
            break;
         case "image/png":
            $this->tipo = "png";
            $this->image = imagecreatefrompng($this->tnome);
            break;
      }
      return 'true';
   }
   
   function pegarSavedImagem($imagem)
   {
       /**
        * Pega uma imagem que já está salva no computador.
        * @param string $imagem
        */
       
       try
       {
           $tipo = substr($imagem,-3);
           if(!in_array($tipo,array("jpg","png","gif")))
           {
               return "Imagem inválida";
           }

           $this->tipo = $tipo;           
           // Pega o nome da imagem
           $this->nome = explode("/",$imagem);
           $this->nome = array_pop($this->nome);           
           $len = strlen($this->nome)-3;
           $this->nome = substr($this->nome,$len);
           $this->nome = $this->nome."_box";
           $this->tnome = $imagem;
           
           // Cria a imagem
           switch ($this->tipo)
           {
              case "jpg":
                 $this->image = imagecreatefromjpeg($this->tnome);
                 break;
              case "gif":
                 $this->image = imagecreatefromgif($this->tnome);
                 break;
              case "png":
                 $this->image = imagecreatefrompng($this->tnome);
                 break;
           }
           return true;
       }
       catch(Exception $a)
       {
           return "Erro ao abrir imagem.";
       }
   }
   
   function formatoImg()
   {
      return $this->tipo;
   }
   
   public function setNome()
   {
       /**
        *  Pega o nome da imagem sendo utilizada.
        *  @return string;
        */
       
       return $this->nome;
   }

   function verificarMaximo($tm)
   {
      if ($this->size > $tm || $this->size == "")
         return false;
      else
         return true;
   }

   function dimensoesMaximas($x, $y)
   {
      if (!is_numeric($x) || !is_numeric($y))
         return false;
      $this->tmx = $x;
      $this->tmy = $y;
   }

   function cropOn()
   {
      $this->redimensionar = false;
      $this->crop = true;
   }

   function redimensionaOn()
   {
      $this->crop = false;
      $this->redimensionar = true;
   }

   private function redimensionar()
   {
      $canvas = getimagesize($this->tnome);
      $largura = $canvas[0];
      $altura = $canvas[1];

      if ($largura > $this->tmx)
      {
         $largura = $this->tmx;
         $altura = ($largura * $canvas[1]) / $canvas[0];
      }

      if ($altura > $this->tmy)
      {
         $altura = $this->tmy;
         $largura = ($canvas[0] * $altura) / $canvas[1];


         if ($largura > $this->tmx)
         {
            $largura = $this->tmx;
            $altura = ($largura * $canvas[1]) / $canvas[0];
         }
      }
      $this->rx = $largura;
      $this->ry = $altura;
      $this->imageresized = imagecreatetruecolor($this->rx, $this->ry);
      imagealphablending($this->imageresized, false);
      imagesavealpha($this->imageresized, true);
      imagecopyresampled($this->imageresized, $this->image, 0, 0, 0, 0, $largura, $altura, $canvas[0], $canvas[1]);
   }

   private function crop()
   {
      if($this->tnome == "")
          return;
      $canvas = getimagesize($this->tnome);
      $largura = $canvas[0];
      $altura = $canvas[1];
      
      $this->imagecroped = imagecreatetruecolor($this->tmx,$this->tmy);
      
      // O cropping deve ser feito em um quadrado, logo tmx e tmy devem ser iguais.
      if($this->tmx > $this->tmy)
          $this->tmy = $this->tmx;
      elseif($this->tmy > $this->tmx)
          $this->tmx = $this->tmy;
      
      if ($largura > $altura)
      {
         $n_h = $this->tmy;
         $porcentagem_w = (100*$n_h)/$altura;
         $n_w = ($largura/100)*$porcentagem_w;
         imagecopyresampled($this->imagecroped ,$this->image,0,0,0,0, $n_w, $n_h, $largura , $altura);
      }
      elseif (($altura > $largura ) || ($this->tmx == $this->tmy ))
      {
         $n_w = $this->tmx;
         $porcentagem_h = (100*$n_w)/$largura;
         $n_h = ($altura/100)*$porcentagem_h;
         imagecopyresampled($this->imagecroped ,$this->image,0,0,0,0, $n_w, $n_h, $largura , $altura);
      }
   }
   
   public function generate($local,$renomeia=false)
   {
       /**
        *  Gera uma nova imagem no local especificado.
        *  @param string $local 
        */
              
       if($renomeia == true)
       {
           $this->existeImagem($local);
           $local = $this->filename;
           $filename = explode("/",$local);
           $this->nome = array_pop($filename);
       }
       
          
       if($this->redimensionar == true)
       {
          $this->redimensionar();
          $im2 = $this->imageresized;
       }
       if($this->crop == true)
       {
          $this->crop();               
          $im2 = $this->imagecroped;
       }
       if ($this->tipo == "jpg" || $this->tipo == "jpeg")
       {
          imagejpeg($im2, $local.".".$this->tipo, 100);
          return true;
       }
       elseif ($this->tipo == "png")
       {
          imagepng($im2,$local.".".$this->tipo, 9);
          return true;
       }
       elseif ($this->tipo == "gif")
       {
          imagegif($im2,$local.".".$this->tipo, 9);
          return true;
       }
       else
           return true;
   }
   
   private function existeImagem($local)
   {
       do
       {
           if(file_exists($local.".".$this->tipo))
           {
               // O arquivo já existe, vamos precisar renomea-lo.
               if($this->filenamecount >= 1)
                   $local = substr($local,0,-1);               
               $local = $local."".$this->filenamecount;                      
               $this->filenamecount++;
               $a = false;
           }
           else
           {
               $this->filename = $local;
               $a = true;         
           }
               
       }
       while($a != true);
   }
}
?>