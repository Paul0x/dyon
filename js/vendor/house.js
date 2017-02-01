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
 *  File: house.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

houseInterface = function() {

    this.root = $("#dir-root").val();

    this.bindSelectEvent = function() {

        $("#finance-event-select").bind("change", function() {
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

    this.bindButtons = function() {
        var self = this;
        $("#button-add-house").bind("click", function() {
            self.createHouse(null, 1);
        });

        $(".menu-house-list .item").bind("click", function() {
            var house = $(this).attr("house");
            self.loadHouse(house);
        });

    };

    this.createHouse = function(house, step) {
        var self = this;
        switch (step) {
            case 1:
                var html = "<div class='form-info-wrap house-create-form'>";
                html += "<header>";
                html += "<div class='title'>Adicionar Casa</div>";
                html += "</header>";
                html += "<div class='form-edit-infos'>";
                html += "<div class='info'>Utilize o formulário abaixo para adicionar uma nova unidade de hospedagem. Dentro das unidades de hospedagem você poderá adicionar quartos e assimilar eles a grupos do seu evento.</div>";
                html += "<div class='header'>Informações Básicas</div>";
                html += "<div class='item'>";
                html += "<label>Nome da Casa</label>";
                html += "<input type='text' name='nome' class='add-house-input' />";
                html += "</div>";
                html += "<div class='item'>";
                html += "<label>Endereço<div class='subinfo'>Endereço completo da casa.</div></label>";
                html += "<input type='text' class='biginput add-house-input' name='endereco' />";
                html += "</div>";
                html += "<div class='header'>Vagas e Valores</div>";
                html += "<div class='item'>";
                html += "<label>Quantidade de Vagas</label>";
                html += "<input type='text' name='num_vagas' class='add-house-input' />";
                html += "</div>";
                html += "<div class='item'>";
                html += "<label>Valor por Pessoa</label>";
                html += "<input type='text' name='valor_pessoa' class='add-house-input' />";
                html += "</div>";
                html += "<div class='header'>Quartos</div>";
                html += "<div class='item'>";
                html += "<label>Número de Quartos</label>";
                html += "<input type='text' name='num_quartos' class='smallinput add-house-input'  />";
                html += "</div>";
                html += "</div>";
                html += "<input type='submit' class='btn-01 add-house-step1' value='Próximo Passo'  />";
                html += "</div>";
                html += "<div class='add-house-error'></div>";
                $(".right-content-house").html(html);
                $("input[name=valor_pessoa]").maskMoney({prefix: 'R$ ', allowNegative: false, thousands: '.', decimal: ',', affixesStay: false});
                $(".add-house-step1").die().live("click", function() {
                    var house = new Object();
                    house.nome = $(".add-house-input[name='nome']").val();
                    house.endereco = $(".add-house-input[name='endereco']").val();
                    house.valor_pessoa = $(".add-house-input[name='valor_pessoa']").val().replace(".", "").replace(",", ".");
                    house.quantidade_vagas = parseInt($(".add-house-input[name='num_vagas']").val());
                    house.numero_quartos = parseInt($(".add-house-input[name='num_quartos']").val());
                    self.createHouse(house, 2);
                });
                break;
            case 2:
                if (house.nome === "" || house.endereco === "" || house.valor_pessoa === "") {
                    $(".add-house-error").html("Preencha os campos corretamente para continuar.");
                    return;
                }
                if (isNaN(house.quantidade_vagas) || isNaN(house.numero_quartos)) {
                    $(".add-house-error").html("A quantidade de vagas e o número de quartos precisam ser numéricos..");
                    return;
                }
                var html = "<header>";
                html += "<div class='title'>Adicionar Casa</div>";
                html += "</header>";
                html += "<div class='add-house-preview-infos'>";
                html += "<div class='title'>Informações da Casa Selecionada</div>";
                html += "<div class='info'>";
                html += "<label>Nome</label> " + house.nome;
                html += "</div>";
                html += "<div class='info'>";
                html += "<label>Endereço</label> " + house.endereco;
                html += "</div>";
                html += "<div class='info'>";
                html += "<label>Valor x Quantidade</label> " + house.quantidade_vagas + "un x R$" + house.valor_pessoa + " = " + (house.quantidade_vagas * house.valor_pessoa);
                html += "</div>";
                html += "<div class='clear'></div>";
                html += "</div>";
                html += "<div class='form-edit-infos'>";
                html += "<div class='info'>Agora selecione o nome dos quartos e as configurações opcionais.</div>";

                for (var i = 0; i < house.numero_quartos; i++) {
                    html += "<div class='house-room-add-item'>";
                    html += "<div class='num'>Quarto " + (i + 1) + "</div>";
                    html += "<input type='text' class='add-house-quarto-numero' name='quarto-" + i + "-numero' placeholder='Número' />";
                    html += "<input type='text' class='add-house-quarto-capacidade' name='quarto-" + i + "-vagas' placeholder='Vagas' />";
                    html += "<select class='add-house-quarto-tipo' name='quarto-" + i + "-tipo' >";
                    html += "<option value='0'>Convencional</option>";
                    html += "<option value='1'>Suíte</option>";
                    html += "</select>";
                    html += "</div>";
                }
                html += "<input type='button' class='btn-01 add-house-step2' value='Finalizar Adição'  />";
                html += "</div>";
                $(".house-create-form").html(html);
                $(".add-house-error").html("");
                $(".add-house-step2").die().live("click", function() {
                    house.quartos = Array();
                    $(".house-room-add-item").each(function(index, value) {
                        house.quartos[index] = new Object();
                        house.quartos[index].numero = parseInt($("input[name=quarto-" + index + "-numero]").val());
                        house.quartos[index].vagas = parseInt($("input[name=quarto-" + index + "-vagas]").val());
                        house.quartos[index].tipo = $("select[name=quarto-" + index + "-tipo]").val();
                    });
                    self.createHouse(house, 3);
                });
                break;
            case 3:
                var quarto_count_vagas = 0;
                var error_flag = false;
                $.each(house.quartos, function(index, quarto) {
                    if (isNaN(quarto.vagas) || isNaN(quarto.numero)) {
                        error_flag = true;
                    }
                    quarto_count_vagas += quarto.vagas;
                });

                if (error_flag === true) {
                    $(".add-house-error").html("O número do quarto e o número de vagas precisam ser numéricos.");
                    return;
                }

                if (house.quantidade_vagas !== quarto_count_vagas) {
                    $(".add-house-error").html("O número de vagas especificado é diferente do número de vagas distribuido nos quartos.");
                    return;
                }

                $(".add-house-error").html("");
                $.ajax({
                    url: self.root + "/casas/ajax",
                    data: {
                        mode: "add_house_form",
                        nome: house.nome,
                        endereco: house.endereco,
                        valor_pessoa: house.valor_pessoa,
                        quantidade_vagas: house.quantidade_vagas,
                        quartos: house.quartos
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            location.reload();
                        } else {
                            $(".add-house-error").html(data.error);
                        }
                    }
                });
                break;
        }

    };

    this.loadHouse = function(house) {
        var self = this;
        $.ajax({
            url: self.root + "/casas/ajax",
            data: {
                mode: "load_house",
                house: house,
                render: true
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $(".right-content-house").html(data.return);
                    var comments_interface = new commentsInterface();
                    comments_interface.bindCommentsButtons();
                    comments_interface.loadComments("#comments-box-8-" + house, 8, house);
                    $(".house-list-rooms .room").die().live("click", function()
                    {
                        var room = parseInt($(this).attr("room"));
                        self.loadRoom(room);
                    });
                } else {
                }
            }
        });
    };

    this.loadRoom = function(room) {
        var self = this;
        if (isNaN(room)) {
            return;
        }
        $.ajax({
            url: self.root + "/casas/ajax",
            data: {
                mode: "load_room",
                id_room: room
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "<div class='ajax-box-title'>Informações do Quarto [ " + data.room.numero + " ] - \"" + data.room.casa.nome + "\"</div>";
                    html += "<div class='room-info-wrap'>";
                    html += "<div class='room-info'>";
                    html += "<div class='info'>";
                    html += "<label>Número de vagas</label> " + data.room.ocupados + "/" + data.room.numero_vagas;
                    html += "</div>";
                    html += "<div class='info'>";
                    html += "<label>Suíte</label> " + data.room.suite;
                    html += "</div>";
                    html += "</div>";
                    if (data.room.members) {
                        html += "<div class='members'>";
                        html += "<div class='title'>Membros do Quarto</div>"
                        $.each(data.room.members, function(index, member) {
                            html += "<div class='member' pacote='" + member.id + "'>";
                            html += "<div class='column-nome'>" + member.nome_usuario + "</div>";
                            html += "<div class='column-grupo'>" + member.nome_grupo + " [" + member.codigo_acesso + "]</div>";
                            html += "<div class='column-status'>" + member.status + "</div>";
                            html += "<div class='column-remove' pacote='" + member.id + "'>Remover</div>";
                            html += "<div class='clear'></div>";
                            html += "</div>";
                        });
                    }
                    html += "</div>";
                    html += '<input type="button" class="btn-01" id="room-add-members" value="Adicionar Membros">';
                    html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
                    html += "</div>";
                    loadBigAjaxBox(html);

                    $("#room-add-members").die().live("click", function() {
                        self.addMembersForm(data.room);
                    });

                    $(".room-info-wrap .members .member .column-remove").die().live("click", function() {
                        var pacote = parseInt($(this).attr("pacote"));
                        self.removeMemberFromRoom(pacote);
                    });

                } else {
                }
            }
        });
    };

    this.addMembersForm = function(room)
    {
        var self = this;
        var html = "<div class='ajax-box-title'>Adicionar Membros no Quarto [ " + room.numero + " ] - \"" + room.casa.nome + "\"</div>";
        html += "<input type='hidden' value='" + room.id + "' id='room-add-members-room-id' />"
        html += "<div class='ajax-box-info'>Você pode adicionar membros no quarto pesquisando através do nome de usuário ou através do grupo do membro. Selecione no lado esquerdo o tipo de pesquisa que você deseja fazer e, ao selecionar, o membro aparecerá no lado direito.</div>";
        html += "<div id='room-add-members-wrap'>";
        html += "<div class='left-board box'>";
        html += "<div class='title'>Membros Selecionados</div>";
        html += "<div id='room-add-selected-member-wrap'>";
        html += "</div>";
        html += "<div class='error'></div>";
        html += "<input type='button' value='Finalizar' class='btn-02' id='room-add-member-button-submit' />";
        html += '<input type="button" class="btn-03 ajax-close-box" value="Fechar">';
        html += "</div>";
        html += "<div class='right-board box'>";

        html += "<div class='title'>Selecione os Membros</div>";
        html += "<div class='tab-select'>";
        html += "<div class='tab' id='room-add-member-tab-group'>Grupos</div>";
        html += "<div class='tab' id='room-add-member-tab-people'>Pessoas</div>";
        html += "<div class='clear'></div>";
        html += "</div>";
        html += "<div id='room-add-member-search-wrap'></div>";
        html += "<div id='room-add-member-results-wrap'></div>";
        html += "</div>";
        html += "</div>";

        loadBigAjaxBox(html);
        self.loadMemberAddGroup();
        $("#room-add-members-wrap .tab-select .tab").die().live("click", function() {
            switch (this.id) {
                case "room-add-member-tab-group":
                    self.loadMemberAddGroup();
                    break;
                case "room-add-member-tab-people":
                    self.loadMemberAddPeople();
                    break;
            }
        });

        $("#room-add-member-results-wrap .member-row").die().live("click", function() {
            self.addSelectedMember(this);
        });

        $("#room-add-selected-member-wrap .member-row span").die().live("click", function() {
            self.removeSelectedMember(this);
        });

        $("#room-add-member-button-submit").die().live("click", function() {
            self.submitRoomAddMember();
        });

    };

    this.loadMemberAddGroup = function() {
        var self = this;
        var html = "<input type='text' placeholder='Digite o código do grupo' id='room-add-member-query-group' />";
        html += "<input type='button' value='Pesquisar' class='btn-01' id='room-add-member-button-group'/>";
        $("#room-add-member-search-wrap").html(html);

        $("#room-add-member-button-group").die().live("click", function() {
            var query = $("#room-add-member-query-group").val();
            $.ajax({
                url: self.root + "/casas/ajax",
                data: {
                    mode: "add_member_search_group",
                    query: query
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = "";
                        $.each(data.results, function(index, pacote) {
                            html += "<div class='member-row' pacote='" + pacote.id + "'><div class='column-nome'>" + pacote.nome + "</div>";
                            if (parseInt(pacote.status_pacote) === 2) {
                                html += "<span class='aprovado'>A</span>";
                            } else if (parseInt(pacote.status_pacote) === 3) {
                                html += "<span class='quitado'>Q</span>";
                            }
                            html += "</div>";
                        });

                        $("#room-add-member-results-wrap").html(html);
                    } else {
                        $("#room-add-member-results-wrap").html("<div class='error'>Nenhum membro encontrado.</div>");
                    }
                }
            });
        });
    };

    this.loadMemberAddPeople = function() {
        var self = this;
        var html = "<input type='text' placeholder='Digite o nome do usuário' id='room-add-member-query-people' />";
        html += "<input type='button' value='Pesquisar' class='btn-01' id='room-add-member-button-people'/>";
        $("#room-add-member-search-wrap").html(html);

        $("#room-add-member-button-people").die().live("click", function() {
            var query = $("#room-add-member-query-people").val();
            $.ajax({
                url: self.root + "/casas/ajax",
                data: {
                    mode: "add_member_search_people",
                    query: query
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = "";
                        $.each(data.results.list, function(index, pacote) {
                            html += "<div class='member-row' pacote='" + pacote[6] + "'><div class='column-nome'>" + pacote[0] + "</div>";
                            if (parseInt(pacote[4]) === 2) {
                                html += "<span class='aprovado'>A</span>";
                            } else if (parseInt(pacote[4]) === 3) {
                                html += "<span class='quitado'>Q</span>";
                            }
                            html += "</div>";
                        });
                        $("#room-add-member-results-wrap").html(html);

                    } else {
                        $("#room-add-member-results-wrap").html("<div class='error'>Nenhum membro encontrado.</div>");
                    }
                }
            });
        });
    };

    this.addSelectedMember = function(row) {
        var self = this;
        var member = new Object();
        member.pacote = parseInt($(row).attr("pacote"));
        member.nome = $("#room-add-member-results-wrap .member-row[pacote=" + member.pacote + "] .column-nome").html();
        var html = "<div class='member-row' pacote='" + member.pacote + "'>" + member.nome + "<span class='remove' pacote='" + member.pacote + "'>x</span></div>";
        $("#room-add-selected-member-wrap").append(html);
        $("#room-add-member-results-wrap .member-row[pacote=" + member.pacote + "]").remove();
    };

    this.removeSelectedMember = function(row) {
        var self = this;
        var pacote = parseInt($(row).attr("pacote"));
        $("#room-add-selected-member-wrap .member-row[pacote=" + pacote + "]").remove();
    };

    this.submitRoomAddMember = function() {
        var self = this;
        var pacotes = new Array();
        var id = parseInt($("#room-add-members-room-id").val());
        $("#room-add-selected-member-wrap .member-row").each(function(index, member) {
            pacotes.push(parseInt($(this).attr("pacote")));
        });

        $.ajax({
            url: self.root + "/casas/ajax",
            data: {
                mode: "submit_add_members",
                room: id,
                pacotes: pacotes
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    closeAjaxBox();
                    self.loadHouse(data.house);
                } else {
                    $("#room-add-members-wrap .error").html("<div class='ajax-box-error'>" + data.error + "</div>");
                }
            }
        });

    };

    this.removeMemberFromRoom = function(id_pacote) {
        var self = this;

        if (isNaN(id_pacote)) {
            return;
        }

        $.ajax({
            url: self.root + "/casas/ajax",
            data: {
                mode: "remove_member",
                member: id_pacote
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $(".room-info-wrap .members .member[pacote='" + id_pacote + "']").remove();
                } else {
                }
            }
        });
    };
};