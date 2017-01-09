/**
 *  Setup para requisições AJAX.
 */
$.ajaxSetup({
      type: 'POST',
      data:
              {
                    majax: true
              }
});

/**
 *   Pega o diretório padrão do sistema.
 */
$(document).ready(function() {
      root = $("#dir-root").val();
      format = new formatStr();
      sideMenuHeightFix();
});
