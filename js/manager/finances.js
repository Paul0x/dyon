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
 *  File: finances.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

financeInterface = function () {

    this.root = $("#dir-root").val();
    var list_compras;
    var compra = new Object();
    this.bindSelectEvent = function () {

        $("#finance-event-select").bind("change", function () {
            var new_event = this.value;
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "change_event",
                    event: new_event
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        location.reload();
                    }
                }
            });
        });
    };
    this.loadListCompras = function () {
        $(document).ready(function () {
            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "load_compras"
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        var html = "";
                        list_compras = data.compras;
                        $.each(data.compras, function (index, categoria) {
                            $("#compra-categoria-list").append("<div class='item categoria-list-box' id='categoria-box-" + categoria.id + "'>" + index + "</div>");
                            html += "<div class='compra-list-categoria-box' id='categoria-" + categoria.id + "'>";
                            html += "<h2>" + index + " (R$" + categoria.valor_total + ")</h2>";
                            $.each(categoria.itens, function (index2, compra) {
                                html += "<a href='" + root + "/manager/financeiro/compra/" + compra.id + "'><div class='item'>";
                                html += compra.nome;
                                if (compra.tipo === '0')
                                    html += "<div class='compra-label-tipo label-compra' title='Compra'>C</div>";
                                if (compra.tipo === '1')
                                    html += "<div class='compra-label-tipo label-orcamento' title='Orçamento'>O</div>";
                                if (parseInt(compra.status) === 0)
                                    html += "<div class='compra-label-tipo label-cancelada' title='Pacote Cancelado'>C</div>";
                                if (parseInt(compra.status) === 1)
                                    html += "<div class='compra-label-tipo label-pendente' title='Pacote Pendente'>P</div>";
                                if (parseInt(compra.status) === 2)
                                    html += "<div class='compra-label-tipo label-aprovada' title='Pacote Aprovado'>A</div>";

                                html += "<div class='finance-info'>";
                                html += compra.quantidade + "un x R$" + compra.valor_unitario + " = R$" + compra.valor_total + " em " + compra.num_parcelas + "x";
                                html += "</div>";
                                html += "</div></a>";
                            });
                            html += "</div>";
                        });
                        $("#compra-list-wrap").html(html);
                    }
                }
            });
        });
        $(document).on("click", ".categoria-list-box", function () {
            var categoria_id = this.id.split("-")[2];
            var self = this;
            $.each(list_compras, function (index, categoria) {

                if (categoria.id == categoria_id) {
                    if (categoria.visibility == '1') {
                        $("#categoria-" + categoria.id).css("display", "none");
                        $(self).removeClass("selected-item");
                        list_compras[index].visibility = '0';
                    } else {
                        $("#categoria-" + categoria.id).css("display", "block");
                        $(self).addClass("selected-item");
                        list_compras[index].visibility = '1';
                    }
                } else {
                    if (categoria.visibility == '1') {
                        list_compras[index].visibility = '1';
                    } else {
                        $("#categoria-" + categoria.id).css("display", "none");
                    }
                }
            });
        });
    };
    this.bindCategoryAdd = function () {
        $("#compra-add-categoria-btn").bind("click", function () {
            var html = "<div class='ajax-box-title'>Adicionar Categoria</div>";
            html += "<div class='ajax-box-info'>Você pode categorizar suas compras utilizando o sistema de categorias, utilize o formulário abaixo para adicionar um novo tipo de categoria.</div>";
            html += "<input type='text' name='nome' class='compra-categoria-add-input' id='compra-add-categoria-nome' placeholder='Nome da categoria' />";
            html += '<input type="button" class="btn-01 add-categoria-submit" id="compra-add-categoria-btn-submit" value="Adicionar">';
            html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
            loadAjaxBox(html);
        });
        $(document).on("click", "#compra-add-categoria-btn-submit", function () {
            var nome = $("#compra-add-categoria-nome").val();
            if (nome === "") {
                ajaxBoxMessage("O nome da categoria não pode estar em branco.", "error");
                return;
            }
            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "add_categoria",
                    nome: nome
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        ajaxBoxMessage("Categoria adicionada, crie uma nova compra para utiliza-la.", "success");
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        });
    };
    this.bindCompraAdd = function () {
        $("#compra-add-compra-btn").bind("click", function () {
            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "add_compra_form",
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        loadAjaxBox(data.html);
                        $(".compra-add-form[name=vunitario]").maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        });
        $(document).on("click", "#compra-add-compra-parcelas", function () {
            compra.nome = $(".compra-add-form[name=nome]").val();
            compra.tipo = $(".compra-add-form[name=tipo]").val();
            compra.categoria = $(".compra-add-form[name=categoria]").val();
            compra.valor_unitario = parseFloat($(".compra-add-form[name=vunitario]").val().replace(".", "").replace(",", ".")).toFixed(2);
            compra.quantidade = parseInt($(".compra-add-form[name=quantidade]").val());
            compra.num_parcelas = parseInt($(".compra-add-form[name=nparcelas]").val());
            if (isNaN(compra.num_parcelas) === true || compra.num_parcelas <= 0) {
                ajaxBoxMessage("O número de parcelas precisa ser um numeral inteiro maior que 0.", "error");
                return;
            }
            if (compra.nome == "" || isNaN(compra.valor_unitario) === true || isNaN(compra.quantidade) === true) {
                ajaxBoxMessage("Algumas informações da compra estão inválidas.", "error");
                return;
            }


            var html = "<div class='ajax-box-title'>Adição de Compras</div>";
            html += "<div class='ajax-box-info'>Insira o valor das parcelas nos campos abaixo, lembrando que a soma do valor das parcelas deve ser igual ao valor total mostrado abaixo.</div>";
            html += "<div class='compra-add-info'>";
            html += "<label>Nome</label> " + compra.nome + "<br/>";
            html += "<label>Tipo</label> " + compra.tipo + "<br/>";
            html += "<label>Valor Total</label> " + compra.quantidade + "un x R$" + compra.valor_unitario;
            html += "</div>";
            html += "<div id='compra-parcela-restante'>Falta Distribuir<br/><span class='valor'></span></div><div class='clear'></div>";
            var i = 0;
            for (i = 0; i < compra.num_parcelas; i++) {
                html += "<div class='parcela-item'>";
                html += "<input type='text' class='compra-parcela-input compra-parcela-input-valor' n_parcela='" + i + "' campo='valor' name='parcela[" + i + "][valor]' placeholder='Valor da parcela' />";
                html += "<input type='text' class='compra-parcela-input compra-parcela-input-vencimento' n_parcela='" + i + "' campo='vencimento' name='parcela[" + i + "][vencimento]' placeholder='Vencimento' />";
                html += "</div>";
            }
            html += "<input type='button' id='compra-add-compra-btn-submit' disabled class='btn-01' value='Finalizar Adição'/>";
            html += "<input type='button' class='btn-03 ajax-close-box' value='Fechar'/>";
            $(".compra-add-form").html(html);
            $(".compra-parcela-input-valor").maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});
            $(".compra-parcela-input-vencimento").mask("99/99/9999");
            $("#compra-parcela-restante span").html("R$" + (compra.valor_unitario * compra.quantidade).toFixed(2)).addClass("color-green");
        });
        $(document).on("change", ".compra-parcela-input-valor", function () {
            var total_parcelas = 0;
            var css_class;
            var total_compras = (compra.valor_unitario * compra.quantidade).toFixed(2);
            $(".compra-parcela-input-valor").each(function (index, parcela) {
                if (isNaN(parseFloat($(this).val().replace(".", "").replace(",", "."))) === false)
                    total_parcelas += parseFloat($(this).val().replace(".", "").replace(",", "."));
            });
            var diff = total_compras - total_parcelas;
            if (diff >= 0)
                css_class = ["color-green", "color-red"];
            else
                css_class = ["color-red", "color-green"];
            $("#compra-parcela-restante span").html("R$" + (diff).toFixed(2)).removeClass(css_class[1]).addClass(css_class[0]);
            if (diff == 0) {
                $("#compra-add-compra-btn-submit").removeAttr("disabled");
            } else {
                $("#compra-add-compra-btn-submit").attr("disabled", "true");
            }

        });
        $(document).on("click", "#compra-add-compra-btn-submit", function () {
            var i = 0;
            compra.parcelas = new Array();
            for (i = 0; i < compra.num_parcelas; i++) {
                var valor = $(".compra-parcela-input-valor[n_parcela=" + i + "]").val().replace(".", "").replace(",", ".");
                var vencimento = $(".compra-parcela-input-vencimento[n_parcela=" + i + "]").val();
                compra.parcelas[i] = {valor: valor, vencimento: vencimento};
            }

            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "add_compra",
                    nome: compra.nome,
                    tipo: compra.tipo,
                    categoria: compra.categoria,
                    quantidade: compra.quantidade,
                    valor_unitario: compra.valor_unitario,
                    parcelas: compra.parcelas

                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        location.reload();
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        });
    };
    this.bindCompraButtons = function () {
        var self = this;
        $(document).off("click", ".compra-infos .status .edit").on("click", ".compra-infos .status .edit", function () {
            var compra = $(this).attr("compra");
            self.loadEditCompraStatusForm(compra);
        });

        $(document).off("click", "#edit-compra-type").on("click", "#edit-compra-type", function () {
            var compra = $(this).attr("compra");
            self.loadEditCompraType(compra);
        });

        $(document).off("click", "#edit-compra-quantity").on("click", "#edit-compra-quantity", function () {
            var compra = $(this).attr("compra");
            var step = parseInt($(this).attr("step"));

            if (step === 2) {
                self.editCompraQuantity(compra);
            } else {
                self.loadEditQuantityForm(compra);
            }
        });

        $(document).off("click", "#compra-add-parcela").on("click", "#compra-add-parcela", function () {
            var compra = $(this).attr("compra");
            self.loadAddParcelaForm(compra);
        });

        $(document).off("click", ".compra-parcelas .buttons .button-edit").on("click", ".compra-parcelas .buttons .button-edit", function () {
            var parcela = $(this).attr("parcela");
            var step = $(this).attr("step");
            self.loadEditParcelaForm(parcela, step, this);
        });
        $(document).off("click", ".compra-parcelas .buttons .button-confirm").on("click", ".compra-parcelas .buttons .button-confirm", function () {
            var parcela = $(this).attr("parcela");
            self.loadConfirmParcelaForm(parcela);
        });
        $(document).off("click", ".compra-parcelas .buttons .button-cancel").on("click", ".compra-parcelas .buttons .button-cancel", function () {
            var parcela = $(this).attr("parcela");
            self.loadCancelParcelaForm(parcela);
        });
        $(document).off("click", ".compra-parcelas .buttons .button-view").on("click", ".compra-parcelas .buttons .button-view", function () {
            var parcela = $(this).attr("parcela");
            self.loadViewParcelaForm(parcela);
        });
    };

    this.loadEditQuantityForm = function (compra) {
        var self = this;
        var quantity = parseInt($("#compra-infos-" + compra + " .quantity-number").html().slice(0, -2));
        if (isNaN(quantity)) {
            return;
        }
        $("#compra-infos-" + compra + " .quantity-number").html("<input type='text' class='compra-quantity-input' name='quantity' value='" + quantity + "' />");
        $("#edit-compra-quantity").html("Finalizar").attr("step", 2);
    };

    this.editCompraQuantity = function (compra) {
        var self = this;
        var quantity = parseInt($(".compra-quantity-input").val());
        if (isNaN(quantity)) {
            return;
        }

        $.ajax({
            url: root + "/manager/financeiro/ajax",
            data: {
                mode: "edit_compra_quantity",
                compra: compra,
                quantity: quantity

            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    var html = quantity + "un";
                    $("#compra-infos-" + compra + " .quantity-number").html(html);
                    $("#edit-compra-quantity").html("Editar Quantidade").attr("step", 1);
                }
            }
        });
    };

    this.loadEditCompraStatusForm = function (compra) {
        var html = "";
        html += "<span class=\"compra-status-aprovada compra-status-edit\" status=\"2\">Aprovada</span>";
        html += "<span class=\"compra-status-pendente compra-status-edit\" status=\"1\">Pendente</span>";
        html += "<span class=\"compra-status-cancelada compra-status-edit\" status=\"0\">Cancelada</span>";
        $(".compra-infos .status").html(html);
        $(document).off("click", ".compra-status-edit").on("click", ".compra-status-edit", function () {
            var status = parseInt($(this).attr("status"));
            if (status !== 0 && status !== 1 && status !== 2)
                return;
            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "edit_compra_status",
                    compra: compra,
                    status: status

                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        html = "";
                        switch (parseInt(data.status))
                        {
                            case 0:
                                html += "<span class=\"compra-status-cancelada compra-status-edit\" status=\"0\">Cancelada</span>";
                                break;
                            case 1:
                                html += "<span class=\"compra-status-pendente compra-status-edit\" status=\"1\">Pendente</span>";
                                break;
                            case 2:
                                html += "<span class=\"compra-status-aprovada compra-status-edit\" status=\"2\">Aprovada</span>";
                                break;
                        }
                        html += "<div class='edit' compra='" + compra + "'>Alterar</div>";
                        $(".compra-infos .status").html(html);
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        });
    };

    this.loadEditCompraType = function (compra) {
        $.ajax({
            url: root + "/manager/financeiro/ajax",
            data: {
                mode: "edit_compra_type",
                compra: compra
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    $(".compra-infos .tipo").html(data.tipo);
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };

    this.loadAddParcelaForm = function (compra) {
        var self = this;
        var html = "<div class='ajax-minibox ajax-minibox-form-1 compra-add-form'>";
        html += "<div class='ajax-box-title'>Adicionar Parcela</div>";
        html += "<div class='ajax-box-info'>Adicione uma nova parcela para essa compra utilizando o formulário abaixo. <br/><b>Ao adicionar uma parcela o valor unitário da compra será aumentado.</b></div>";
        html += "<div class='item'>";
        html += "<label>Valor da Parcela</label>";
        html += "<div><input type='text' name='valor' placeholder='ex. R$5000,00'></div>";
        html += "</div>";
        html += "<div class='item'>";
        html += "<label>Data de Vencimento</label>";
        html += "<div><input type='text' name='vencimento' placeholder='ex. 28/02/2015'></div>";
        html += "</div>";
        html += "</div>";
        html += "<input type='button' id='compra-parcela-add' class='btn-01' value='Adicionar Parcela' />";
        html += "<input type='button' class='btn-03 ajax-close-box' value='Fechar'>";
        loadAjaxBox(html);
        $(".compra-add-form input[name='valor']").maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});
        $(".compra-add-form input[name='vencimento']").mask("99/99/9999");

        $(document).off("click", "#compra-parcela-add").on("click", "#compra-parcela-add", function () {
            var valor = $(".compra-add-form input[name='valor']").val();
            var vencimento = $(".compra-add-form input[name='vencimento']").val();
            $.ajax({
                url: self.root + "/manager/financeiro/ajax",
                data: {
                    mode: "add_parcela",
                    compra: compra,
                    vencimento: vencimento,
                    valor: valor.replace(".", "").replace(",", ".")
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        location.reload();
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });


        });
    };

    this.loadEditParcelaForm = function (parcela, step, button) {
        if (parseInt(step) === 1) {
            var vencimento = $("#parcela-" + parcela + " .data-vencimento").html();
            var valor = $("#parcela-" + parcela + " .value").html().substr(2);
            $("#parcela-" + parcela + " .data-vencimento").html("<input id='edit-parcela-vencimento-" + parcela + "' type='text' value='" + vencimento + "' />");
            $("#edit-parcela-vencimento-" + parcela).mask("99/99/9999");
            $("#parcela-" + parcela + " .value").html("<input id='edit-parcela-value-" + parcela + "' type='text' value='" + valor + "' />");
            $("#edit-parcela-value-" + parcela).maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});

            $(button).attr("step", 2);
            $(button).html("Finalizar Edição");
        } else if (parseInt(step) === 2) {
            var vencimento = $("#edit-parcela-vencimento-" + parcela).val();
            var valor = $("#edit-parcela-value-" + parcela).val();
            $.ajax({
                url: root + "/manager/financeiro/ajax",
                data: {
                    mode: "edit_parcela",
                    parcela_id: parcela,
                    vencimento: vencimento,
                    valor: valor.replace(".", "").replace(",", ".")
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        $("#parcela-" + parcela + " .data-vencimento").html(vencimento);
                        $("#parcela-" + parcela + " .value").html("R$" + valor);
                        $(button).attr("step", 1);
                        $(button).html("Editar Vencimento");
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        }
    };

    this.loadCancelParcelaForm = function (parcela) {
        $.ajax({
            url: root + "/manager/financeiro/ajax",
            data: {
                mode: "load_parcela_info",
                parcela_id: parcela
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    var html = "<div class='ajax-box-title'>Cancelar Parcela</div>";
                    html += "<div class='ajax-box-info'>Confirme o cancelamento da parcela através do botão abaixo. Lembrando que parcelas essa ação não pode ser desfeita.</div>";
                    if (parseInt(data.parcela.status) === 2) {
                        html += "<p class='warntext'>ATENÇÃO: Essa parcela aparenta já estar confirmada, tenha certeza antes de efetuar o cancelamento.</p>";
                    }
                    html += '<input type="button" class="btn-01 cancel-parcela-submit" id="cancel-parcela-submit-' + data.parcela.id + '" value="Cancelar Parcela">';
                    html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
                    loadAjaxBox(html);
                    $(document).off("click", ".cancel-parcela-submit").on("click", ".cancel-parcela-submit", function () {
                        var id = this.id.split('-')[3];
                        $.ajax({
                            url: root + "/manager/financeiro/ajax",
                            data: {
                                mode: "cancel_parcela_submit",
                                parcela_id: parcela
                            },
                            success: function (data) {
                                data = eval("( " + data + " )");
                                if (data.success === "true") {
                                    closeAjaxBox();
                                    $("#parcela-" + parcela + " .status").html("<div class=\"compra-parcela-status cancelada\">Cancelada</div>");
                                    $("#parcela-" + parcela + " .buttons").remove();
                                } else {
                                    ajaxBoxMessage(data.error, "error");
                                }
                            }
                        });
                    });
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };

    this.loadViewParcelaForm = function (parcela) {
        $.ajax({
            url: root + "/manager/financeiro/ajax",
            data: {
                mode: "load_parcela_info",
                parcela_id: parcela
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    var html = "<div class='ajax-box-title'>Comprovante da Parcela</div>";
                    html += "<div class='ajax-box-info'>O comprovante da parcela atual pode ser visualizado pelo link abaixo.</div>";
                    if (parseInt(data.parcela.tipo_comprovante) === 1)
                        html += "<a href='" + root + "/comprovante/compra/" + data.parcela.id_comprovante + "' target='new'><img src='" + root + "/comprovante/compra/" + data.parcela.id_comprovante + "' class='parcela-comprovante-thumb' /></a>";
                    if (parseInt(data.parcela.tipo_comprovante) === 2)
                        html += "<div class='parcela-comprovante-show-cod'>Código da Transação: <strong>" + data.parcela.id_comprovante + "</strong></div>";
                    html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
                    loadAjaxBox(html);
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };


    this.loadConfirmParcelaForm = function (parcela) {
        $.ajax({
            url: root + "/manager/financeiro/ajax",
            data: {
                mode: "load_parcela_info",
                parcela_id: parcela
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    var html = '<div class="ajax-minibox ajax-minibox-form-1 parcela-confirm-box">';
                    html += '<div class="ajax-box-title">Confirmação de Parcela</div>';
                    html += '<div class="ajax-box-info">Adicione a nota fiscal da parcela ou o código de transação referente ao pagamento dela.</div>';
                    html += '<div class="item">';
                    html += '<label>Id da Parcela</label>';
                    html += '<div>' + data.parcela.id + '</div>';
                    html += '</div>';
                    html += '<div class="item">';
                    html += '<label>Valor</label>';
                    html += '<div>' + data.parcela.valor_str + '</div>';
                    html += '</div>';
                    html += '<div class="item">';
                    html += '<label>Data</label>';
                    html += '<div>Vencimento em <strong>' + data.parcela.data_vencimento_str + '</strong></div>';
                    html += '</div>';
                    html += '<form id="parcela-confirm-upload" name="parcela-confirm-upload" method="post" enctype="multipart/form-data">';
                    html += '<div class="field">';
                    html += '<label>Comprovante da Parcela</label>';
                    html += '<div class="parcela-select-comprovante-type">';
                    html += '<div class="type selected" id="parcela-select-comprovante-type-file">Arquivo</div>';
                    html += '<div class="type" id="parcela-select-comprovante-type-cod">Código da Transação</div>';
                    html += '<div class="clear"></div>';
                    html += '</div>';
                    html += '<input id="parcela-field-id" value="' + data.parcela.id + '" name="id" type="hidden">';
                    html += '<input id="parcela-field-tipo-comprovante" value="1" name="tipo-comprovante" type="hidden">';
                    html += '<div class="parcela-select-comprovante-type-box-file">';
                    html += '<input id="parcela-field-comprovante-file" name="comprovante-file" type="file">';
                    html += '</div>';
                    html += '<div class="parcela-select-comprovante-type-box-cod">';
                    html += '<input id="parcela-field-comprovante-cod" name="comprovante-cod" placeholder="Insira o código da transação..." type="text">';
                    html += '</div>';
                    html += '</div>';
                    html += '<input id="confirm-pacote-submit" class="btn-01" value="Confirmar Parcela" type="button">';
                    html += '<input class ="btn-03 ajax-close-box" value ="Fechar" type =  "button" > ';
                    html += '</form>';
                    html += "</div>";
                    loadAjaxBox(html);
                    $(document).off("click", ".parcela-select-comprovante-type .type").on("click", ".parcela-select-comprovante-type .type", function () {
                        if (this.id === "parcela-select-comprovante-type-file") {
                            $(".parcela-select-comprovante-type-box-file").stop(true, true).fadeIn();
                            $(".parcela-select-comprovante-type-box-cod").stop(true, true).fadeOut();
                            $("#parcela-field-tipo-comprovante").val(1);
                        } else {
                            $(".parcela-select-comprovante-type-box-file").stop(true, true).fadeOut();
                            $(".parcela-select-comprovante-type-box-cod").stop(true, true).fadeIn();
                            $("#parcela-field-tipo-comprovante").val(2);
                        }
                    });
                    $(document).off("click", "#confirm-pacote-submit");
                    $(document).on("click", "#confirm-pacote-submit", function () {

                        var parcela_id = $("#parcela-field-id").val();
                        var tipo_comprovante = $("#parcela-field-tipo-comprovante").val();
                        var comprovante;
                        if (parseInt(tipo_comprovante) === 1) {
                            if ($("#parcela-field-comprovante-file").length > 0) {
                                comprovante = document.getElementById("parcela-field-comprovante-file").files[0];
                            }
                        } else if (parseInt(tipo_comprovante) === 2) {
                            comprovante = $("#parcela-field-comprovante-cod").val();
                        }
                        var form = new FormData();
                        var xhr = new XMLHttpRequest();
                        form.append("mode", "confirm_parcela_submit");
                        form.append("parcela_id", parcela_id);
                        form.append("comprovante", comprovante);
                        form.append("tipo_comprovante", tipo_comprovante);
                        xhr.open('POST', root + "/manager/financeiro/ajax", true);
                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 2) {
                                $("#confirm-pacote-submit").val("Carregando...").attr("disabled", "disabled");
                            }
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                $("#confirm-pacote-submit").val("Confirmar Parcela").removeAttr("disabled");
                                var data = eval("(" + xhr.responseText + ")");
                                if (data.success === "true") {
                                    $("#parcela-" + data.parcela_id + " .status").html("<div class=\"compra-parcela-status aprovada\">Aprovada</div>");
                                    $("#parcela-" + data.parcela_id + " .buttons .button-confirm").remove();
                                    $("#parcela-" + data.parcela_id + " .buttons").append("<div class='button button-view' parcela='" + data.parcela_id + "'>Visualizar Comprovante</div>");
                                    $("#parcela-" + data.parcela_id + " .data-pagamento").html(data.data_pagamento);
                                    closeAjaxBox();
                                } else {
                                    ajaxBoxMessage(data.error, "error");
                                }
                            }
                        };
                        xhr.send(form);
                    });
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };
};


flowInterface = function () {

    this.root = $("#dir-root").val();
    this.initFlow = function () {
        var self = this;
        $.ajax({
            url: self.root + "/manager/financeiro/fluxo",
            data: {
                mode: "loadFlowSettings"
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    switch (parseInt(data.flow)) {
                        case 1:
                            self.loadDailyFlow(null);
                            break;
                        case 2:
                            self.loadMonthlyFlow(null);
                            break;
                        case 3:
                            self.loadYearlyFlow();
                            break;
                    }
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
        $(document).off("click", "#flow-interface-wrap .item").on("click", "#flow-interface-wrap .item", function () {
            var interface = $(this).attr("ref");
            $.ajax({
                url: self.root + "/manager/financeiro/fluxo",
                data: {
                    mode: "setFlowInterface",
                    interface: interface
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        switch (parseInt(data.flow)) {
                            case 1:
                                self.loadDailyFlow(null);
                                break;
                            case 2:
                                self.loadMonthlyFlow(null);
                                break;
                            case 3:
                                self.loadYearlyFlow(null);
                                break;
                        }
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        });
    };
    this.loadYearlyFlow = function (year) {
        var self = this;
        if (year === undefined) {
            year = 2015;
        }
        var html = "<div class='flow-select-date'>";
        html += "<div class='title'>Fluxo Anual</div>";
        html += "<label target='date-select'>Selecione a Data</label>";
        html += "<select id='yearly-date-select' name='date-select' ></select>";
        html += "</div>";
        html += "<div id='flow-table'>";
        html += "<div class='header'>";
        html += "<div class='item-tipo item'>Tipo</div>";
        html += "<div class='item-desc item'>Descrição</div>";
        html += "<div class='item-valor item'>Valores</div>";
        html += "</div>";
        html += "</div>";
        $("#flow-wrap").html(html);
        var date = new Date();
        var current_year = date.getFullYear();
        var year_select = "";
        current_year = current_year - 5;
        for (var i = 0; i <= 10; i++) {
            year_select += "<option value='" + current_year + "' ";
            if (current_year === year)
                year_select += "selected ";
            year_select += ">" + current_year + "</option>";
            current_year++;
        }
        $("#yearly-date-select").html(year_select);
        for (var i = 1; i <= 12; i++) {
            $.ajax({
                url: self.root + "/manager/financeiro/fluxo",
                async: false,
                data: {
                    mode: "loadMonthlyFlow",
                    month: i,
                    year: year
                },
                success: function (data) {
                    data = eval("(" + data + " )");
                    if (data.success === "true") {
                        self.fillTable(data.flow.table);
                    } else {
                        ajaxBoxMessage(data.error, "error");
                    }
                }
            });
        }

        $(document).off("change", "#yearly-date-select").on("change", "#yearly-date-select", function (event) {
            var ano = this.value;
            self.loadYearlyFlow(parseInt(ano));
        });
    };
    this.loadMonthlyFlow = function (month, year) {
        var self = this;
        var html = "<div class='flow-select-date'>";
        html += "<div class='title'>Fluxo Mensal</div>";
        html += "<label target='date-select'>Selecione a Data</label>";
        html += "<select id='monthly-date-select' name='date-select' ></select>";
        html += "</div>";
        html += "<div id='flow-table'>";
        html += "<div class='header'>";
        html += "<div class='item-tipo item'>Tipo</div>";
        html += "<div class='item-desc item'>Descrição</div>";
        html += "<div class='item-valor item'>Valores</div>";
        html += "</div>";
        html += "</div>";
        $("#flow-wrap").html(html);
        $.ajax({
            url: self.root + "/manager/financeiro/fluxo",
            data: {
                mode: "loadMonthlyFlow",
                month: month,
                year: year
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    var month_select = "";
                    $.each(data.select_array, function (index, month) {
                        month_select += "<option value='" + month.value + "' ";
                        if (month.value === data.flow.date)
                            month_select += "selected ";
                        month_select += ">" + month.string + "</option>";
                    });
                    $("#monthly-date-select").html(month_select);
                    self.fillTable(data.flow.table);
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
        $(document).off("change", "#monthly-date-select").on("change", "#monthly-date-select", function (event) {
            var data = this.value.split("/");
            var mes = parseInt(data[0]);
            var ano = parseInt(data[1]);
            self.loadMonthlyFlow(mes, ano);
        });
    };
    this.loadDailyFlow = function (day, month, year) {
        var self = this;
        var html = "<div class='flow-select-date'>";
        html += "<div class='title'>Fluxo Diário</div>";
        html += "<label target='date-select'>Selecione a Data</label>";
        html += "<input type='text' maxlength='10' name='date-select' id='daily-date-select' placeholder='  /  /    '/>";
        html += "</div>";
        html += "<div id='flow-table'>";
        html += "<div class='header'>";
        html += "<div class='item-tipo item'>Tipo</div>";
        html += "<div class='item-desc item'>Descrição</div>";
        html += "<div class='item-valor item'>Valores</div>";
        html += "</div>";
        html += "</div>";
        $("#flow-wrap").html(html);
        $("#daily-date-select").mask("99/99/9999");
        $.ajax({
            url: self.root + "/manager/financeiro/fluxo",
            data: {
                mode: "loadDailyFlow",
                day: day,
                month: month,
                year: year
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    $("#daily-date-select").val(data.flow.date);
                    self.fillTable(data.flow.table);
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
        $(document).off("keyup", "#daily-date-select").on("keyup", "#daily-date-select", function (event) {
            if (event.keyCode === 13) {
                var data = this.value.split("/");
                var dia = parseInt(data[0]);
                var mes = parseInt(data[1]);
                var ano = parseInt(data[2]);
                self.loadDailyFlow(dia, mes, ano);
            }
        });
    };
    this.fillTable = function (table) {
        var self = this;
        var html = "";
        $.each(table, function (index, data) {
            html += "<div class='row ";
            if (data.tipo === 'D') {
                html += "row-despesa";
            }
            if (data.tipo === 'R') {
                html += "row-receita";
            }
            if (data.tipo === 'SA') {
                html += "row-sa";
            }
            html += "' ";
            if (data.id !== "") {
                html += "id='" + data.id + "' ";
            }
            html += ">";
            html += "<div class='item-tipo item'>" + data.tipo + "</div>";
            html += "<div class='item-desc item'>" + data.desc + "</div>";
            html += "<div class='item-valor item'>R$" + data.valor + "</div>";
            html += "</div>";
        });
        html += "<div class='clear'></div>";
        $("#flow-table").append(html);
        $(document).off("click", "#flow-table .row").on("click", "#flow-table .row", function () {
            if (this.id === "")
                return;
            var info = this.id.split("-");
            self.loadFullDesc(info[1], info[2], info[3]);
        });
    };

    this.loadFullDesc = function (flow, type, date) {
        var self = this;
        var html = "<div class='ajax-box-title'>Descrição de Valores</div>";
        html += "<div id='ajax-flow-desc-table'>";
        html += "</div>";
        html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
        loadBigAjaxBox(html);
        $.ajax({
            url: self.root + "/manager/financeiro/fluxo",
            data: {
                mode: "loadFlowDesc",
                flow: flow,
                type: type,
                date: date
            },
            success: function (data) {
                data = eval("(" + data + " )");
                if (data.success === "true") {
                    switch (type) {
                        case 'r':
                            self.fillDescReceitaTable(data.transactions);
                            break;
                        case 'd':
                            self.fillDescDespesaTable(data.transactions);
                            break;
                    }
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };

    this.fillDescReceitaTable = function (transactions) {
        var self = this;
        var html = "";
        $.each(transactions, function (index, parcelas) {
            if (index === 'p') {
                html += "<div class='title'>Lista de Parcelas Pagas</div>";
                html += "<div class='header'>";
                html += "<div class='title-date'>Pagamento</div>";
                html += "<div class='title-nome'>Cliente</div>";
                html += "<div class='title-valor'>Valor</div>";
                html += "<div class='title-tipo'>Tipo</div>";
                html += "</div>";
                $.each(parcelas.t, function (i, parcela) {
                    html += "<div class='item'>";
                    html += "<div class='field-date'>" + parcela.data_pagamento + "</div>";
                    html += "<div class='field-nome'><a href='" + self.root + "/cliente/" + parcela.id_usuario + "'>" + parcela.nome + "</a> (" + parcela.email + ")</div>";
                    html += "<div class='field-valor'>" + parcela.valor + "</div>";
                    html += "<div class='field-tipo'>";
                    if (parseInt(parcela.tipo_pagamento) === 3)
                        html += "<i>Depósito</i>";
                    if (parseInt(parcela.tipo_pagamento) === 2)
                        html += "<i>Pagseguro</i>";
                    if (parseInt(parcela.tipo_pagamento) === 1)
                        html += "<i>Boleto</i>";
                    html += "</div>";
                    html += "</div>";
                    html += "</div>";
                });
                html += "<div class='counter'>" + parcelas.c + " parcelas totalizando R$" + parcelas.v + "</div>";
            } else if (index === 'np') {
                html += "<div class='title'>Lista de Parcelas Não Pagas</div>";
                html += "<div class='header'>";
                html += "<div class='title-date'>Vencimento</div>";
                html += "<div class='title-nome'>Cliente</div>";
                html += "<div class='title-valor'>Valor</div>";
                html += "<div class='title-tipo'>Tipo</div>";
                html += "</div>";
                $.each(parcelas.t, function (i, parcela) {
                    html += "<div class='item'>";
                    html += "<div class='field-date'>" + parcela.data_vencimento + "</div>";
                    html += "<div class='field-nome'><a href='" + self.root + "/cliente/" + parcela.id_usuario + "'>" + parcela.nome + "</a> (" + parcela.email + ")</div>";
                    html += "<div class='field-valor'>" + parcela.valor + "</div>";
                    html += "<div class='field-tipo'>";
                    if (parseInt(parcela.tipo_pagamento) === 3)
                        html += "<i>Depósito</i>";
                    if (parseInt(parcela.tipo_pagamento) === 2)
                        html += "<i>Pagseguro</i>";
                    if (parseInt(parcela.tipo_pagamento) === 1)
                        html += "<i>Boleto</i>";
                    html += "</div>";
                    html += "</div>";
                    html += "</div>";
                });
                html += "<div class='counter'>" + parcelas.c + " parcelas totalizando R$" + parcelas.v + "</div>";
            }
        });

        $("#ajax-flow-desc-table").html(html);
    };

    this.fillDescDespesaTable = function (transactions) {
        var self = this;
        var html = "";
        $.each(transactions, function (index, parcelas) {
            if (index === 'p') {
                html += "<div class='title'>Lista de Compras</div>";
                html += "<div class='header'>";
                html += "<div class='title-date'>Vencimento</div>";
                html += "<div class='title-nome'>Compra</div>";
                html += "<div class='title-valor'>Valor</div>";
                html += "<div class='title-tipo'>Status</div>";
                html += "</div>";
                $.each(parcelas.t, function (i, parcela) {
                    html += "<div class='item'>";
                    html += "<div class='field-date'>" + parcela.data_vencimento + "</div>";
                    html += "<div class='field-nome'><a href='" + self.root + "/manager/financeiro/compra/" + parcela.id_compra + "'>" + parcela.nome + "</a> [ " + parcela.categoria + " ]</div>";
                    html += "<div class='field-valor'>" + parcela.valor + "</div>";
                    html += "<div class='field-tipo'>";
                    if (parseInt(parcela.status) === 2)
                        html += "<i>Paga</i>";
                    if (parseInt(parcela.status) === 1)
                        html += "<i>Pendente</i>";
                    html += "</div>";
                    html += "</div>";
                    html += "</div>";
                });
                html += "<div class='counter'>" + parcelas.c + " parcelas totalizando R$" + parcelas.v + "</div>";
            }
        });

        console.log("ayy2");
        $("#ajax-flow-desc-table").html(html);
    };
};