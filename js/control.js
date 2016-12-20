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
 *  File: control.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

controlInterface = function() {

    this.bindClienteListMore = function() {
        $("#list-view-more").bind("click", function() {
            var query_searched = $("#query-searched").val();
            var field_searched = $("#field-searched").val();
            var page = parseInt($("#list-page-count").val());
            $("#list-view-more").stop(true, true).fadeOut();
            $.ajax({
                url: root + "/controle/cliente",
                data: {
                    querystring: query_searched,
                    fieldquery: field_searched,
                    page: page
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        if (data.clientes_count < 25) {
                            $("#list-view-more").remove();
                        }
                        if (data.clientes_count > 0) {
                            $("#list-page-count").val(page + data.clientes_count);
                            $(".item-list").append(data.clientes_html);
                            $("#list-view-more").stop(true, true).fadeIn();
                        }
                    }
                }
            });
        });
    };

    this.bindSelectEvent = function() {
        $("#control-event-select").bind("change", function() {
            var new_event = this.value;
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "change_event",
                    event: new_event
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        location.reload();
                    }
                }
            });
        });
    };

    this.bindUserIdByName = function() {
        $("#pacote-create-nome-completefield").bind("keyup", function() {
            var name = this.value;
            if (name === "") {
                $(".autocomplete").remove();
                return;
            }
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "search_cliente_id",
                    name: name
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        setAutocompleteForms("pacote-create-nome-completefield", data.names, "pacote-create-nome-id");
                    }
                    else {
                        $(".autocomplete").remove();
                    }
                }
            });
        });
    };

    this.bindGroupCreation = function() {
        $("#pacote-create-grupo-add").bind("click", function() {
            var html = '<input type="text" name="grupo_add" placeholder="Nome do Grupo" value="' + $("#pacote-create-nome-completefield").val() + '" />';
            $("#pacote-create-grupo-input").html(html);
        });
        $("#pacote-create-grupo-select").bind("click", function() {
            var event_id = $("#pacote-create-selected-event").val();
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "get_groups_select",
                    event_id: event_id
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = "<select name='grupo_select'>";
                        html += "<option value=''>Selecione um Grupo</option>";
                        $.each(data.groups, function(index, grupo) {
                            html += "<option value='" + grupo[0] + "'>" + grupo[1] + " [ RG: " + grupo[3] + " | Líder: " + grupo[2] + " ]</option>";
                        });
                        html += "</select>";
                        $("#pacote-create-grupo-input").html(html);
                    }
                }
            });
        });
    };

    this.bindGetParcelas = function() {
        $("#pacote-create-pagamento, #pacote-create-lote").bind("change", function() {
            if (this.id === "pacote-create-lote") {
                if ($("#pacote-create-pagamento").val() === "") {
                    return;
                }
            }
            var lote = $("#pacote-create-lote").val();
            var forma_pagamento = $("#pacote-create-pagamento").val();
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    forma_pagamento: forma_pagamento,
                    mode: "get_max_parcelas",
                    lote_id: lote
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = "";
                        $.each(data.parcelas, function(index, parcela) {
                            html += "<option value='" + parcela[0] + "'>" + parcela[1] + "</option>";
                        });
                        $("#pacote-create-parcelas").html(html);
                    }
                }
            });
        });
    };

    this.bindChangeOrderListPacotes = function() {
        $(".list-column-header").bind("click", function() {
            var order_label = $(this).attr("orderby");
            var current_order;
            var no_change = false;
            var current_order_string = $("#order-input").val();
            if (current_order_string === "" || current_order_string === "[]")
                current_order = new Object();
            else {
                current_order = JSON.parse(current_order_string);
            }
            $.each(current_order, function(index, order) {
                if (index === order_label) {
                    if (order.mode === "ASC") {
                        current_order[index].mode = "DESC";
                    } else {
                        delete current_order[index];
                        no_change = true;
                    }
                }
            });
            if (current_order[order_label] === undefined && no_change === false) {
                current_order[order_label] = {field: order_label, mode: "ASC"};
            }
            var new_order_string = JSON.stringify(current_order);
            $("#order-input").val(new_order_string);
            $("#list-pacote-form-wrap").submit();
        });
    };

    this.bindPacoteListMore = function() {
        $("#list-view-more").bind("click", function() {
            var query_searched = $("#query-searched").val();
            var field_searched = $("#field-searched").val();
            var status_searched = $("#status-searched").val();
            var pagamento_searched = $("#pagamento-searched").val();
            var lote_searched = $("#lote-searched").val();
            var order_searched = $("#order-searched").val();
            var page = parseInt($("#list-page-count").val());

            status_searched = eval("( " + status_searched + " )");

            $("#list-view-more").stop(true, true).fadeOut();
            $.ajax({
                url: root + "/controle/pacote",
                data: {
                    querystring: query_searched,
                    fieldquery: field_searched,
                    order: order_searched,
                    query_status: status_searched,
                    query_pagamento: pagamento_searched,
                    query_lote: lote_searched,
                    page: page
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        if (data.pacotes_count < 20) {
                            $("#list-view-more").remove();
                        }
                        if (data.pacotes_count > 0) {
                            $("#list-page-count").val(page + data.pacotes_count);
                            $("#pacote-list-wrap").append(data.pacotes_html);
                            $("#list-view-more").stop(true, true).fadeIn();
                        }
                    }
                }
            });
        });
    };

    this.bindParcelaListMore = function() {
        $("#list-view-more").bind("click", function() {
            var page = parseInt($("#list-page-count").val());
            var status = parseInt($("#status-parcela").val());
            $.ajax({
                url: root + "/controle/pacote/parcelas/" + status,
                data: {
                    page: page
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        if (data.pacotes_count < 20) {
                            $("#list-view-more").remove();
                        }
                        if (data.pacotes_count > 0) {
                            $("#list-page-count").val(page + data.pacotes_count);
                            $(".item-list").append(data.pacotes_html);
                        }
                    }
                }
            });
        });
    };
};

clienteOverviewInterface = function() {

    this.root = $("#dir-root").val();
    var self = this;
    this.bindPacoteButtons = function() {
        $(".button-pacote-change-status").bind("click", function() {
            self.changePacoteStatus(this.id);
        });
        $(".button-pacote-parcelas").bind("click", function() {
            self.getPacoteParcelas(this.id.split("-")[3]);
        });
        $(".button-pacote-comments").bind("click", function() {
            self.loadPacoteComments(this.id.split("-")[3]);
        });

        $(".button-pacote-watchlog").bind("click", function() {
            if (parseInt($(this).attr("step")) === 2) {
                self.pacoteEditInfo(this.id.split("-")[3]);
            } else {
                self.loadPacoteEditForm(this.id.split("-")[3]);
            }
        });

        $(".parcela").live("mouseenter", function() {
            var id = this.id.split("-")[1];
            $("#parcela-edit-cancel-" + id + ", #parcela-edit-confirm-" + id + ", #parcela-edit-comprovante-" + id + ", #parcela-edit-edit-" + id).stop(true, true).fadeIn(150);
        });

        $(".parcela").live("mouseleave", function() {
            var id = this.id.split("-")[1];
            $("#parcela-edit-cancel-" + id + ", #parcela-edit-confirm-" + id + ", #parcela-edit-comprovante-" + id + ", #parcela-edit-edit-" + id).stop(true, true).fadeOut(150);
        });

        $(".parcela-edit-confirm").live("click", function() {
            self.parcelaConfirmForm(this.id);
        });

        $(".parcela-edit-cancel").live("click", function() {
            self.parcelaCancelForm(this.id);
        });

        $(".parcela-edit-comprovante").live("click", function() {
            self.parcelaComprovanteForm(this.id);
        });

        $(".parcela-edit-edit").live("click", function() {
            self.parcelaEditForm(this.id);
        });

        $(".parcela-select-comprovante-type .type").live("click", function() {
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

        $(".cancel-parcela-submit").live("click", function() {
            self.parcelaCancel(this.id);
        });

        $(".parcela-add-button").live("click", function() {
            self.addParcelaForm(this.id);
        });

        self.countPacoteComments();
    };


    this.loadPacoteEditForm = function(pacote) {
        var self = this;
        $.ajax({
            url: self.root + "/controle/ajax",
            data: {
                mode: "pacote_load_info",
                id: pacote
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html_select_lotes = "<select name='lote-edit' pacote='" + data.pacote.id_pacote + "'>";
                    $.each(data.lotes, function(index, lote) {
                        html_select_lotes += "<option value='" + lote.id + "' ";
                        if (lote.id === data.pacote.id_lote)
                            html_select_lotes += "selected ";
                        html_select_lotes += ">" + lote.nome + "(" + lote.status_string + ")</option>";
                    });
                    html_select_lotes += "</select>";
                    $("#item-pacote-" + pacote + " .field-lote").html(html_select_lotes);
                    $("#item-pacote-" + pacote + " .field-grupo").html("<input type='text' name='grupo-edit' pacote='" + data.pacote.id_pacote + "' placeholder='Código do Grupo'/>");
                    $("#item-pacote-" + pacote + " .button-pacote-watchlog").attr("step", 2).html("Finalizar Edição");
                }
            }
        });
    };


    this.pacoteEditInfo = function(pacote) {
        var self = this;
        var lote = $("#item-pacote-" + pacote + " select[name='lote-edit']").val();
        var grupo = $("#item-pacote-" + pacote + " input[name='grupo-edit']").val();

        $.ajax({
            url: self.root + "/controle/ajax",
            data: {
                mode: "pacote_edit_info",
                id: pacote,
                lote: lote,
                grupo: grupo
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#item-pacote-" + pacote + " .field-lote").html(data.pacote.lote);
                    $("#item-pacote-" + pacote + " .field-grupo").html(data.pacote.codigo_acesso + "(" + data.pacote.nome_grupo + ")");
                    $("#item-pacote-" + pacote + " .button-pacote-watchlog").attr("step", 1).html("Alterar Informações");
                }
            }
        });
    };

    this.changePacoteStatus = function(id) {
        id = id.split("-")[4];
        if ($(".pacote-change-status-wrap").length === 0) {
            var html = "<div class='pacote-change-status-wrap'>";
            html += "<span>Selecione o status:</span> ";
            html += "<input type='button' class='pacote-change-status-aprovado pacote-change-status-select' status='2' pacote='" + id + "' value='Aprovado' />";
            html += "<input type='button' class='pacote-change-status-pendente pacote-change-status-select' status='1' pacote='" + id + "' value='Pendente' />";
            html += "<input type='button' class='pacote-change-status-cancelado pacote-change-status-select' status='0' pacote='" + id + "' value='Cancelado' />";
            html += "</div>";
            $("#item-pacote-" + id + " .control").prepend(html);
            $(".pacote-change-status-select").bind("click", function() {
                var id = this.getAttribute("pacote");
                var status = this.getAttribute("status");

                $.ajax({
                    url: root + "/controle/ajax",
                    data: {
                        mode: "change_pacote_status",
                        pacote_id: id,
                        status: status
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            $(".pacote-change-status-wrap").remove();
                            data.status = parseInt(data.status);
                            switch (data.status) {
                                case 0:
                                    html = '<div class="list-cliente-pagamento-cancelado">Cancelado</div>';
                                    break;
                                case 1:
                                    html = '<div class="list-cliente-pagamento-pendente">Pendente</div>';
                                    break;
                                case 2:
                                    html = '<div class="list-cliente-pagamento-aprovado">Aprovado</div>';
                                    break;
                                case 3:
                                    html = '<div class="list-cliente-pagamento-aprovado">Quitado</div>';
                                    break;
                            }

                            $("#item-pacote-" + id + " .field-status .value").html(html);
                        }
                    }
                });
            });
        }
        ;
    };

    this.getPacoteParcelas = function(id) {
        if ($("#pacote-list-parcelas-" + id).length == 0) {
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "get_pacote_parcelas",
                    pacote_id: id
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = "<div class='pacote-list-parcelas-wrap' id='pacote-list-parcelas-" + id + "'>";
                        html += "<div class='header'>";
                        html += "<div class='field-count'>Parcela</div>";
                        html += "<div class='field-valor'>Valor</div>";
                        html += "<div class='field-data'>Vencimento</div>";
                        html += "<div class='field-data'>Criação</div>";
                        html += "<div class='field-status'>Status</div>";
                        html += "</div>";
                        var i = 1;
                        $.each(data.parcelas, function(index, parcela) {
                            html += "<div class='parcela' id='parcela-" + parcela.id + "'>";
                            html += "<div class='field-count'>" + i + "</div>";
                            html += "<div class='field-valor'>" + parcela.valor + "</div>";
                            html += "<div class='field-data field-vencimento'>" + parcela.data_vencimento + "</div>";
                            html += "<div class='field-data'>" + parcela.data_criacao + "</div>";
                            if (parcela.status == '1') {
                                html += "<div class='field-naopago'></div>";
                            } else if (parcela.status == '2') {
                                html += "<div class='field-pago'></div>";
                            } else if (parcela.status == '0') {
                                html += "<div class='field-cancelado'></div>";
                            } else if (parcela.status == '3') {
                                html += "<div class='field-aguardando'></div>"
                            }
                            if (parcela.status == '1' || parcela.status == '3') {
                                html += "<div class='parcela-edit-button parcela-edit-confirm' id='parcela-edit-confirm-" + parcela.id + "'>Confirmar Pagamento</div>";
                            }
                            else if (parcela.status == '2') {
                                html += "<div class='parcela-edit-button parcela-edit-comprovante' id='parcela-edit-comprovante-" + parcela.id + "'>Visualizar Comprovante</div>";
                            }
                            if (parcela.status != '0') {
                                html += "<div class='parcela-edit-button parcela-edit-cancel' id='parcela-edit-cancel-" + parcela.id + "'>Cancelar Parcela</div>";
                                html += "<div class='parcela-edit-button parcela-edit-edit' id='parcela-edit-edit-" + parcela.id + "'>Editar Parcela</div>";
                            }
                            html += "<div class='clear'></div>";
                            html += "</div>";
                            i++;
                        });
                        html += "<div class='parcela-add-button' id='parcela-add-" + id + "'>Adicionar Parcela</div>";
                        html += "</div>";
                        $("#item-pacote-" + id + " .control").after(html);
                        $("#pacote-list-parcelas-" + id).slideDown();
                        $("#item-pacote-" + id + " .button-pacote-parcelas").html("Esconder Parcelas");
                    }
                }
            });
        } else {
            $("#pacote-list-parcelas-" + id).slideUp(500, function() {
                $("#pacote-list-parcelas-" + id).remove();
                $("#item-pacote-" + id + " .button-pacote-parcelas").html("Ver Parcelas");
            });
        }
    };

    this.parcelaEditForm = function(id) {
        id = id.split('-')[3];
        var valor = $("#parcela-" + id + " .field-valor").html();
        var vencimento = $("#parcela-" + id + " .field-vencimento").html();
        $("#parcela-" + id + " .field-valor").html("<input id='parcela-edit-input-valor-" + id + "' type='text' class='parcela-edit-input' value='" + valor + "' />");
        $("#parcela-" + id + " .field-vencimento").html("<input id='parcela-edit-input-vencimento-" + id + "' type='text' class='parcela-edit-input' value='" + vencimento + "' />");
        $("#parcela-edit-input-valor-" + id).maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});
        $("#parcela-edit-input-vencimento-" + id).mask("99/99/9999");
        $("#parcela-edit-input-valor-" + id + ", #parcela-edit-input-vencimento-" + id).die().bind("keyup", function(event) {
            if (event.keyCode === 13) {
                var vencimento_new = $("#parcela-edit-input-vencimento-" + id).val();
                var valor_new = $("#parcela-edit-input-valor-" + id).val().replace("R$", "").replace(".", "").replace(",", ".").replace(" ", "");
                $.ajax({
                    url: root + "/controle/ajax",
                    data: {
                        mode: "edit_parcela_submit",
                        parcela_id: id,
                        valor: valor_new,
                        vencimento: vencimento_new
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            $("#parcela-" + id + " .field-valor").html(data.parcela.valor);
                            $("#parcela-" + id + " .field-vencimento").html(data.parcela.data_vencimento);
                        }
                        else {

                            $("#parcela-" + id + " .field-valor").html(valor);
                            $("#parcela-" + id + " .field-vencimento").html(vencimento);
                        }
                    }
                });
            }
        });
    };

    this.parcelaConfirmForm = function(id) {
        id = id.split('-')[3];
        $.ajax({
            url: root + "/controle/ajax",
            data: {
                mode: "parcela_confirm_form",
                parcela_id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success == "true") {
                    loadAjaxBox(data.html);
                    $("#confirm-pacote-submit").die("click");
                    $("#confirm-pacote-submit").live("click", function() {

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
                        xhr.open('POST', root + "/controle/ajax", true);
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState == 2) {
                                $("#confirm-pacote-submit").val("Carregando...").attr("disabled", "disabled");
                            }
                            if (xhr.readyState == 4 && xhr.status == 200) {
                                $("#confirm-pacote-submit").val("Confirmar Parcela").removeAttr("disabled");
                                var data = eval("(" + xhr.responseText + ")");
                                if (data.success == "true") {
                                    $("#pacote-list-parcelas-" + data.pacote_id).remove();
                                    self.getPacoteParcelas(data.pacote_id);
                                    closeAjaxBox();
                                }
                                else {
                                    ajaxBoxMessage(data.error, "error");
                                }

                            }
                        };
                        xhr.send(form);
                    });
                }
            }
        });
    };

    this.parcelaComprovanteForm = function(id) {
        id = id.split('-')[3];
        $.ajax({
            url: root + "/controle/ajax",
            data: {
                mode: "parcela_comprovante_form",
                parcela_id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "<div class='ajax-box-title'>Comprovante da Parcela</div>";
                    html += "<div class='ajax-box-info'>O comprovante da parcela atual pode ser visualizado pelo link abaixo.</div>";
                    if (parseInt(data.tipo_comprovante) === 1)
                        html += "<a href='" + root + "/comprovante/parcela/" + data.comprovante + "' target='new'><img src='" + root + "/comprovante/parcela/" + data.comprovante + "' class='parcela-comprovante-thumb' /></a>";
                    if (parseInt(data.tipo_comprovante) === 2)
                        html += "<div class='parcela-comprovante-show-cod'>Código da Transação: <strong>" + data.comprovante + "</strong></div>";
                    html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
                    loadAjaxBox(html);
                }
            }
        });
    };

    this.parcelaCancelForm = function(id) {
        id = id.split('-')[3];
        var html = "<div class='ajax-box-title'>Cancelar Parcela</div>";
        html += "<div class='ajax-box-info'>Ao cancelar uma parcela o valor do pacote será subtraído e você precisará criar outra parcela para compensação. A parcela cancelada fica gravada no sistema.</div>";

        if ($("#parcela-" + id + " .field-pago").length != 0) {
            html += "<p class='warntext'>ATENÇÃO: Essa parcela aparenta já estar confirmada, tenha certeza antes de efetuar o cancelamento.</p>";
        }

        html += '<input type="button" class="btn-01 cancel-parcela-submit" id="cancel-parcela-submit-' + id + '" value="Cancelar Parcela">';
        html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
        loadAjaxBox(html);
    };

    this.parcelaCancel = function(id) {
        id = id.split('-')[3];
        $.ajax({
            url: root + "/controle/ajax",
            data: {
                mode: "cancel_parcela_submit",
                parcela_id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#pacote-list-parcelas-" + data.pacote_id).remove();
                    self.getPacoteParcelas(data.pacote_id);
                    closeAjaxBox();
                }
                else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        });
    };

    this.addParcelaForm = function(id) {
        id = id.split("-")[2];
        if ($("#parcela-add-form-" + id).length == 0) {
            $.ajax({
                url: root + "/controle/ajax",
                data: {
                    mode: "add_parcela_form",
                    pacote_id: id
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success == "true") {
                        $("#parcela-add-" + id).after("<div id='parcela-add-form-" + id + "'>" + data.html + "</div>");
                        $(".parcela-add-submit").bind("click", function() {
                            var valor = $("#parcela-add-form-" + id + " input[name='valor']").val().replace(".", "").replace(",", ".");
                            var vencimento = $("#parcela-add-form-" + id + " input[name='vencimento']").val();
                            $.ajax({
                                url: root + "/controle/ajax",
                                data: {
                                    mode: "add_parcela_submit",
                                    pacote_id: id,
                                    valor: valor,
                                    vencimento: vencimento
                                },
                                success: function(data) {
                                    data = eval("( " + data + " )");
                                    if (data.success === "true") {
                                        $("#pacote-list-parcelas-" + id).remove();
                                        self.getPacoteParcelas(id);
                                        $("#parcela-add-form-" + id).slideUp(200, function() {
                                            $("#parcela-add-form-" + id).remove();
                                        });
                                    }
                                    else {
                                        if ($("#parcela-add-form-" + id + " .error-message01").length == 0) {
                                            $("#parcela-add-form-" + id).append("<div class='error-message01'>" + data.error + "</div>");
                                        } else {
                                            $("#parcela-add-form-" + id + " .error-message01").html(data.error);
                                        }
                                    }
                                }
                            });

                        });
                    }
                }
            });
        }
        else {
            $("#parcela-add-form-" + id).slideUp(200, function() {
                $("#parcela-add-form-" + id).remove();
            });
        }
    };

    this.loadPacoteComments = function(id_pacote) {
        var commentsinterface = new commentsInterface();
        if ($("#item-pacote-" + id_pacote + " .comments-box").length === 0) {
            $("#item-pacote-" + id_pacote).append("<div class='comments-box' id='comments-box-3-" + id_pacote + "'></div>");
            commentsinterface.loadComments("#comments-box-3-" + id_pacote, 3, id_pacote);
        } else {
            $("#item-pacote-" + id_pacote + " .comments-box").remove();
        }
    };

    this.countPacoteComments = function() {
        var commentsinterface = new commentsInterface();
        $(".button-pacote-comments").each(function() {
            var pacote_id = this.id.split("-")[3];
            var button = this;
            commentsinterface.countComments(3, pacote_id, function(response) {
                response = eval("( " + response + " )");
                if (response.success === "true") {
                    $(button).append("<strong class='comment-count'>(" + response.count + ")</strong>");
                }
            });
        });

    };
};

groupInterface = function() {

    this.root = $("#dir-root").val();
    this.init = function() {
        var self = this;
        //this.countGroupComments();
    };

    this.bindButtons = function() {
        var self = this;
        $(".item-list-groups .list .footer-control .button").bind("click", function() {
            var grupo = parseInt($(this).attr("grupo"));
            if (isNaN(grupo))
                return;

            if ($(this).hasClass("button-comments")) {
                self.loadComments(grupo);
            } else if ($(this).hasClass("button-casa")) {

            }
        });

        $(".item-list-groups .list .view-group").bind("click", function() {
            var grupo = parseInt($(this).attr("grupo"));
            if (isNaN(grupo))
                return;

            self.loadGroupInfo(grupo);
        });
    };

    this.loadComments = function(grupo) {
        var self = this;
        var commentsinterface = new commentsInterface();
        if ($("#list-group-" + grupo + " .comments-box").length === 0) {
            $("#list-group-" + grupo).append("<div class='comments-box' id='comments-box-7-" + grupo + "'></div>");
            commentsinterface.loadComments("#comments-box-7-" + grupo, 7, grupo);
        } else {
            $("#list-group-" + grupo + " .comments-box").remove();
        }
    };

    this.countGroupComments = function() {
        var commentsinterface = new commentsInterface();
        $(".item-list-groups .list .footer-control .button-comments").each(function() {
            var grupo = parseInt($(this).attr("grupo"));
            var button = this;
            commentsinterface.countComments(7, grupo, function(response) {
                response = eval("( " + response + " )");
                if (response.success === "true") {
                    $(button).append("<strong class='comment-count'>(" + response.count + ")</strong>");
                }
            });
        });

    };

    this.loadGroupInfo = function(grupo) {
        var self = this;
        $.ajax({
            url: root + "/controle/ajax",
            data: {
                mode: "load_grupo_info",
                id: grupo
            },
            success: function(data) {
                data = eval("( " + data + " )");
                var html = "<div class='ajax-box-title'>Grupo Completo: <b>" + data.grupo.nome + "</b></div>";
                html += "<div id='group-ajax-wrap' grupo='" + data.grupo.id + "'>";
                html += "<div class='info'>";
                html += "<div class='left'>";
                html += "<div class='item'><label>Data de Criação</label><div class='value'>" + data.grupo.data_criacao + "</div></div>";
                html += "<div class='item'><label>Código de Acesso</label><div class='value'>" + data.grupo.codigo_acesso + "</div></div>";
                html += "<div class='item'><label>Casa de Apoio</label><div class='value'>" + data.grupo.casa_apoio + "</div></div>";
                html += "<div class='item'><label>Status do Voucher</label><div class='value'>" + data.grupo.casa_apoio + "</div></div>";
                html += "</div>";
                html += "<div class='left'>";
                html += "<div class='item'><label>Pacotes Aprovados</label><div class='value'>" + data.grupo.num_aprovados + "</div></div>";
                html += "<div class='item'><label>Valor dos Pacotes Aprovados</label><div class='value'>" + data.grupo.valor_total + "</div></div>";
                html += "<div class='item'><label>Pacotes Pendentes</label><div class='value'>" + data.grupo.num_pendentes + "</div></div>";
                html += "</div>";
                html += "<div class='clear'></div>";
                html += "</div>";
                html += "<div class='buttons'>";
                html += "<div class='button-edit-grupo button' grupo='" + data.grupo.id + "'>Editar Informações</div>";
                html += "<div class='button-edit-casa button' grupo='" + data.grupo.id + "'>Editar Casa</div>";
                html += "<div class='ajax-close-box button'>Fechar Janela</div>";
                html += "<div class='clear'></div>";
                html += "</div>";
                html += "<div class='members'>";
                $.each(data.grupo.members, function(index, member) {
                    html += "<div class='member' pacote='" + member.id + "'>";
                    html += "<div class='row-nome'><a href='" + self.root + "/cliente/" + member.id_usuario + "/#item-pacote-" + member.id + "' target='new'>" + member.nome + "</a></div>";
                    html += "<div class='row-lote'>" + member.lote + "</div>";
                    html += "<div class='row-valor'>" + member.valor + "</div>";
                    html += "<div class='row-status'>";
                    switch (parseInt(member.status_pacote)) {
                        case 1:
                            html += "<span class='status-1'>Pendente</span>";
                            break;
                        case 2:
                            html += "<span class='status-2'>Aprovado</span>";
                            break;
                        case 3:
                            html += "<span class='status-3'>Quitado</span>";
                            break;
                        case 4:
                            html += "<span class='status-4'>Cadastrado</span>";
                            break;
                    }
                    html += "</div>";
                    html += "<div class='row-buttons row-edit-pacote' pacote='" + member.id + "'>Editar Pacote</div>";
                    html += "<div class='clear'></div>";
                    html += "</div>";
                });
                html += "</div>";
                html += "<div class='buttons'>";
                html += "<input type='button' class='btn-01 grupo-create-voucher' grupo='" + data.grupo.id + "' value='Criar Voucher' />";
                html += "<input type='button' class='btn-01 grupo-checkin' grupo='" + data.grupo.id + "' value='Check-In' />";
                html += "</div>";
                html += "</div>";
                loadBigAjaxBox(html);
                self.bindAjaxButtons(data.grupo);
            }
        });
    };

    this.bindAjaxButtons = function(grupo) {
        var self = this;
        $(".button-edit-grupo").die().live("click", function() {
            if (isNaN(parseInt(grupo.id))) {
                return;
            }
            self.loadEditGroupForm(grupo);
        });

        $(".row-edit-pacote").die().live("click", function() {
            var pacote = $(this).attr("pacote");
            var step = $(this).attr("step");

            if (parseInt(step) === 2) {
                self.pacoteEditInfo(pacote);
            } else {
                self.loadPacoteEditForm(pacote);
            }
        });

        $(".grupo-checkin").die().live("click", function() {
            if (isNaN(parseInt(grupo.id))) {
                return;
            }
            self.loadCheckInForm(grupo);

        });
    };

    this.loadEditGroupForm = function(grupo) {
        var self = this;
        var html = "<div class='title'>Editar Informações</div>";
        html += "<div class='grupo-edit'>";
        html += "<div class='item'><label>Nome do Grupo</label><div class='value'><input type='text' class='grupo-edit-form' name='nome' value='" + grupo.nome + "' /></div></div>";
        html += "<div class='item'><label>Líder do Grupo</label><div class='value'>";
        html += "<select class='grupo-edit-form' name='lider'>";
        $.each(grupo.members, function(index, member) {
            html += "<option value='" + member.id_usuario + "'";
            if (member.id_usuario === grupo.id_lider) {
                html += " selected ";
            }
            html += ">" + member.nome + "</option>";
        });
        html += "</select>";
        html += "</div></div>";
        html += "<div class='item'><input type='button' class='btn-02 grupo-edit-submit' value='Editar o Grupo' /></div>";
        html += "</div>";
        html += "<div class='clear'></div>";
        $("#group-ajax-wrap[grupo='" + grupo.id + "'] .info").html(html);
        $(".grupo-edit-submit").die().live("click", function() {
            grupo.edited_nome = $(".grupo-edit-form[name='nome']").val();
            grupo.edited_lider = $(".grupo-edit-form[name='lider']").val();
            $.ajax({
                url: self.root + "/controle/ajax",
                data: {
                    mode: "edit_grupo",
                    id: grupo.id,
                    edited_nome: grupo.edited_nome,
                    edited_lider: grupo.edited_lider
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        html = "<div class='left'>";
                        html += "<div class='item'><label>Data de Criação</label><div class='value'>" + data.grupo.data_criacao + "</div></div>";
                        html += "<div class='item'><label>Código de Acesso</label><div class='value'>" + data.grupo.codigo_acesso + "</div></div>";
                        html += "<div class='item'><label>Casa de Apoio</label><div class='value'>" + data.grupo.casa_apoio + "</div></div>";
                        html += "<div class='item'><label>Status do Voucher</label><div class='value'>" + data.grupo.casa_apoio + "</div></div>";
                        html += "</div>";
                        html += "<div class='left'>";
                        html += "<div class='item'><label>Pacotes Aprovados</label><div class='value'>" + data.grupo.num_aprovados + "</div></div>";
                        html += "<div class='item'><label>Valor dos Pacotes Aprovados</label><div class='value'>" + data.grupo.valor_total + "</div></div>";
                        html += "<div class='item'><label>Pacotes Pendentes</label><div class='value'>" + data.grupo.num_pendentes + "</div></div>";
                        html += "</div>";
                        html += "<div class='clear'></div>";
                        $("#group-ajax-wrap[grupo='" + grupo.id + "'] .info").html(html);
                        html = "Grupo Completo: <b>" + data.grupo.nome + "</b>";
                        $(".ajax-box-title").html(html);
                        grupo = data.grupo;
                    } else {


                    }
                }
            });
        });
    };

    this.loadPacoteEditForm = function(pacote) {
        var self = this;
        $.ajax({
            url: self.root + "/controle/ajax",
            data: {
                mode: "pacote_load_info",
                id: pacote
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html_select_lotes = "<select name='lote-edit' pacote='" + data.pacote.id_pacote + "'>";
                    $.each(data.lotes, function(index, lote) {
                        html_select_lotes += "<option value='" + lote.id + "' ";
                        if (lote.id === data.pacote.id_lote)
                            html_select_lotes += "selected ";
                        html_select_lotes += ">" + lote.nome + "(" + lote.status_string + ")</option>";
                    });
                    html_select_lotes += "</select>";
                    $(".member[pacote='" + data.pacote.id_pacote + "'] .row-lote").html(html_select_lotes);
                    $(".member[pacote='" + data.pacote.id_pacote + "'] .row-valor").html("<input type='text' name='grupo-edit' pacote='" + data.pacote.id_pacote + "' placeholder='Código do Grupo'/>");
                    $(".row-edit-pacote[pacote='" + data.pacote.id_pacote + "']").html("Finalizar").attr("step", 2);
                }
            }
        });
    };

    this.pacoteEditInfo = function(pacote) {
        var self = this;
        var lote = $(".member[pacote='" + pacote + "'] select[name='lote-edit']").val();
        var grupo = $(".member[pacote='" + pacote + "'] input[name='grupo-edit']").val();

        $.ajax({
            url: self.root + "/controle/ajax",
            data: {
                mode: "pacote_edit_info",
                id: pacote,
                lote: lote,
                grupo: grupo
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    if (data.pacote.new_group === true) {
                        $(".member[pacote='" + pacote + "']").remove();
                    } else {
                        var html = "<div class='row-nome'><a href='" + self.root + "/cliente/" + data.pacote.id_usuario + "/" + data.pacote.id_pacote + "'>" + data.pacote.nome_usuario + "</a></div>";
                        html += "<div class='row-lote'>" + data.pacote.lote + "</div>";
                        html += "<div class='row-valor'>" + data.pacote.valor_total + "</div>";
                        html += "<div class='row-status'>";
                        switch (parseInt(data.pacote.status_pacote)) {
                            case 1:
                                html += "<span class='status-1'>Pendente</span>";
                                break;
                            case 2:
                                html += "<span class='status-2'>Aprovado</span>";
                                break;
                            case 3:
                                html += "<span class='status-3'>Quitado</span>";
                                break;
                        }
                        html += "</div>";
                        html += "<div class='row-buttons row-edit-pacote' pacote='" + data.pacote.id_pacote + "'>Editar Pacote</div>";
                        html += "<div class='clear'></div>";
                        $(".member[pacote='" + pacote + "']").html(html);
                    }
                }
            }
        });
    };

    this.loadCheckInForm = function(grupo) {
        var self = this;
        var html = "<div class='ajax-box-title'>Realizar Check-In: <b>" + grupo.nome + "</b></div>";
        html += "<div class='ajax-box-info'>Selecione abaixo os membros do grupo que serão registrados no check-in.</div>";
        html += "<div class='ajax-close-box btn-03'>Fechar Janela</div>";
        html += "<div id='checkin-wrap'>";
        html += "<div class='checkin-pacote-select-wrap'>";
        html += "<div class='row'>";
        html += "<div class='column-checkbox'>";
        html += "<input type='checkbox' checked id='checkall' />";
        html += "</div>";
        html += "<div class='column-nome'>";
        html += "<b>Marcar / Desmarcar Todos</b>";
        html += "</div>";
        html += "<div class='clear'></div>";
        html += "</div>";
        $.each(grupo.members, function(index, pacote) {
            if (parseInt(pacote.status_pacote) !== 4 && parseInt(pacote.status_pacote) !== 1 && parseInt(pacote.status_pacote) !== 0) {
                html += "<div class='row'>";
                html += "<div class='column-checkbox'>";
                html += "<input type='checkbox' class='checkin-checkbox-pacote' checked value='" + pacote.id + "' />";
                html += "</div>";
                html += "<div class='column-nome'>";
                html += pacote.nome;
                html += "</div>";
                html += "<div class='column-status'>";
                if (parseInt(pacote.status_pacote) === 2) {
                    html += "<span class='checkin-status checkin-status-aprovado'>Aprovado</span>";
                } else if (parseInt(pacote.status_pacote) === 3) {
                    html += "<span class='checkin-status checkin-status-quitado'>Quitado</span>";
                } else if (parseInt(pacote.status_pacote) === 1) {
                    html += "<span class='checkin-status checkin-status-pendente'>Pendente</span>";
                }
                html += "</div>";
                html += "<div class='clear'></div>";
                html += "</div>";
            }

        });
        html += "</div>";
        html += "<input type='button' class='checkin-button-next-step1 btn-01' value='Confirmar Checkin' />";
        html += "</div>";
        loadBigAjaxBox(html);
        $(".checkin-button-next-step1").die().live("click", function() {
            self.doCheckIn(1, Array(), Array());
        });
    };

    this.doCheckIn = function(step, pacotes_a, pacotes_q) {
        var self = this;
        switch (step) {
            case 1:
                var pacotes = Array();
                $(".checkin-checkbox-pacote").each(function(index, pacote) {
                    if ($(pacote).prop("checked")) {
                        pacotes.push($(pacote).val());
                    }
                });

                $.ajax({
                    url: self.root + "/controle/ajax",
                    data: {
                        mode: "check_pacotes_checkin",
                        pacotes: pacotes
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            if (parseInt(data.count) === 0) {
                                self.doCheckIn(3, Array(), data.pacotes);
                            } else {
                                self.doCheckIn(2, data.pacotes_pendentes, data.pacotes);
                            }
                        } else {
                            ajaxBoxMessage(data.error, "error");
                        }
                    }
                });
                break;
            case 2:
                var html = "<div class='checkin-show-parcelas-wrap'>";
                html += "<div class='info'>As parcelas abaixo estão pendentes para os pacotes selecionados.</div>";
                $.each(pacotes_a, function(index, pacote) {
                    html += "<div class='row' pacote='" + index + "'>";
                    html += "<div class='nome'>" + pacote.nome + "</div>";
                    html += "<div class='parcelas-list'>";
                    $.each(pacote.parcelas, function(idx, parcela) {
                        html += "<div class='parcela'>";
                        html += "<div class='data-vencimento'>" + parcela.data_vencimento + "</div>";
                        html += "<div class='valor'>" + parcela.valor + "</div>";
                        html += "<div class='clear'></div>";
                        html += "</div>";
                    });
                    html += "</div>";
                    html += "</div>";

                });
                html += "</div>";
                html += "<input type='button' class='checkin-button-next-step2 btn-01' value='Confirmar Checkin' />";
                $("#checkin-wrap").html(html);
                $(".checkin-button-next-step2").die().live("click", function() {
                    self.doCheckIn(3, Array(), pacotes_q);
                });

                break;
            case 3:
                $.ajax({
                    url: self.root + "/controle/ajax",
                    data: {
                        mode: "checkin_submit",
                        pacotes: pacotes_q
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            var html = "";
                            html += "<div class='checkin-show-casa-wrap'>";
                            $.each(data.casas, function(index, room) {
                                html += "<div class='room'>";
                                html += "<div class='info'><b>Casa:</b> " + room.nome + " <b>Quarto:</b> " + room.numero + "</div>";
                                html += "<div class='members'>";
                                $.each(room.members, function(idx, membro) {
                                    html += "<div class='member'>" + membro.nome + "</div>";
                                });
                                html += "</div>";
                                html += "</div>";

                            });
                            html += "<div class='clear'></div>";
                            html += "</div>";

                            $("#checkin-wrap").html(html);
                        } else {
                            ajaxBoxMessage(data.success, "error");
                        }
                    }
                });

                break;
        }

    };
};