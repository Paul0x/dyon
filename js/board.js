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
 *  File: board.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

boardInterface = function () {
    this.root = $("#dir-root").val();

    this.initBoards = function () {
        var self = this;
        self.loadBoardsBoxes();
        self.loadUserBoard();
        self.bindBoardSelection();
        self.bindBoardFunctions();
    };

    this.bindBoardFunctions = function () {
        var self = this;
        $("#board-add-board").bind("click", function () {
            self.loadAddBoardForm();
        });
        $("#board-control-form-close").die().live("click", function () {
            self.loadUserBoard();
        });
        $("#board-control-rename").die().live("click", function () {
            self.loadRenameBoardForm();
        });
    };

    this.loadRenameBoardForm = function () {
        var self = this;
        var board_name = $("#board-wrap .board-header .title").html();
        var board_id = $("#board-id-ref").val();
        var html = "<div class='board-control-form' id='board-rename-form'>";
        html += "<div class='title'>Renomear a Board: " + board_name + "</div>";
        html += "<div class='info'>Utilize o formulário abaixo para renomear a board.</div>";
        html += "<div class='item'>";
        html += "<label>Nome da Board</label>";
        html += "<input type='text' name='board-name' value='" + board_name + "'/>";
        html += "</div>";
        html += "<input type='button' id='board-control-form-close' class='btn-03' value='Voltar'/>";
        html += "<input type='button' id='board-rename-form-submit' class='btn-01' value='Adicionar'/>";
        html += "</div>";
        $("#board-wrap").html(html);
        $("#board-rename-form-submit").die().live("click", function () {
            var nome = $("input[name='board-name']").val();
            if (nome === "") {
                self.loadBoardControlFormErrorMessage("O campo nome é obrigatório.");
                return;
            }
            $.ajax({
                url: self.root + "/boards",
                data: {
                    mode: "rename_board",
                    nome: nome,
                    board_id: board_id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        self.removeBoardControlFormErrorMessage();
                        self.loadUserBoard();
                        $("#board-select option[value='" + board_id + "']").html(data.nome);
                    } else {
                        self.loadBoardControlFormErrorMessage(data.error);
                    }
                }
            });
        });
    };

    this.loadAddBoardForm = function () {
        var self = this;
        if ($("#board-add-form").length === 0) {
            var html = "<div class='board-control-form' id='board-add-form'>";
            html += "<div class='title'>Adicionar Nova Board</div>";
            html += "<div class='info'>Utilize o formulário abaixo para criar uma nova board, onde será possível criar tarefas e discussões.</div>";
            html += "<div class='item'>";
            html += "<label>Nome da Board</label>";
            html += "<input type='text' name='board-name'/>";
            html += "</div>";
            html += "<input type='button' id='board-control-form-close' class='btn-03' value='Voltar'/>";
            html += "<input type='button' id='board-add-form-submit' class='btn-01' value='Adicionar'/>";
            html += "</div>";
            $("#board-wrap").html(html);
            $("#board-add-form-submit").die().live("click", function () {
                var nome = $("input[name='board-name']").val();
                if (nome === "") {
                    self.loadBoardControlFormErrorMessage("O campo nome é obrigatório.");
                    return;
                }
                $.ajax({
                    url: self.root + "/boards",
                    data: {
                        mode: "create_new_board",
                        nome: nome
                    },
                    success: function (data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            self.removeBoardControlFormErrorMessage();
                            self.loadUserBoard();
                            self.loadBoardsBoxes();
                        } else {
                            self.loadBoardControlFormErrorMessage(data.error);
                        }
                    }
                });
            });
        }
    };

    this.loadBoardControlFormErrorMessage = function (message) {
        if ($(".error-message").length !== 0)
            $(".error-message").remove();
        $("#board-wrap").append("<div class='error-message'>" + message + "</div>");
    };

    this.removeBoardControlFormErrorMessage = function (message) {
        if ($(".error-message").length !== 0)
            $(".error-message").remove();
        $("#board-wrap").append("<div class='error-message'>" + message + "</div>");
    };

    this.loadBoardsBoxes = function () {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "load_boards_boxes"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-select").html('<option value="select">Selecione uma Board</option>');
                    $.each(data.boards, function (idx, board) {
                        $("#board-select").append("<option value='" + board.id + "'>" + board.nome + "</option>");
                    });
                }
            }
        });
    };

    this.loadUserBoard = function () {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "load_user_board"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-wrap").html(data.html);
                    sideMenuHeightFix()

                }
            }
        });
    };

    this.loadBoardThreads = function (board_id, status) {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "load_board_threads",
                id: board_id,
                status: status
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                }
            }
        });
    };



    this.bindAddTask = function (board_id) {
        var self = this;
        $(".task-add-button").die().live("click", function () {
            if ($("#task-add-form").length === 0) {
                var html = "<div id='task-add-form'>";
                html += "<input type='text' placeholder='Insira o título da tarefa' name='task-titulo' />";
                html += "<input type='text' placeholder='Data de Vencimento (Opcional)' name='task-vencimento' id='task-vencimento-input' />";
                html += "<select name='task-prioridade'>";
                html += "<option value=''>Prioridade</option>";
                html += "<option value='1'>Baixa</option>";
                html += "<option value='2'>Média</option>";
                html += "<option value='3'>Alta</option>";
                html += "<option value='0'>Nenhuma</option>";
                html += "</select>";
                html += "<textarea placeholder='Insira a descrição da tarefa' name='task-desc'></textarea>";
                html += "<input type='button' class='btn-01' id='add-task-submit' board='" + board_id + "' value='Adicionar' />";
                html += "</div>";
                $(".task-add-button").after(html);
                $("#task-vencimento-input").mask("99/99/9999");
                $("#add-task-submit").die().live("click", function () {
                    var titulo = $("input[name='task-titulo']").val();
                    var vencimento = $("input[name='task-vencimento']").val();
                    var prioridade = $("select[name='task-prioridade']").val();
                    var desc = $("textarea[name='task-desc']").val();
                    var board = parseInt($(this).attr('board'));

                    if (isNaN(board)) {
                        return;
                    }

                    $.ajax({
                        url: self.root + "/diretorias",
                        data: {
                            mode: "board_add_task",
                            titulo: titulo,
                            vencimento: vencimento,
                            prioridade: prioridade,
                            desc: desc,
                            board: board
                        },
                        success: function (data) {
                            data = eval("( " + data + " )");
                            if (data.success === "true") {
                                self.loadBoardTasks(board);
                                $("#task-add-form").fadeOut(function () {
                                    $("#task-add-form").remove();
                                });
                            }
                        }
                    });
                });
            } else {
                $("#task-add-form").fadeOut(function () {
                    $("#task-add-form").remove();
                });
            }
        });
    };

    this.bindBoardSelection = function () {
        var self = this;
        $("#board-select").bind("change", function () {
            var id = $(this).val();
            if (isNaN(id)) {
                return;
            }
            $.ajax({
                url: self.root + "/diretorias",
                data: {
                    mode: "update_user_board",
                    id: id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        self.loadUserBoard();
                    }
                }
            });
        });
    };


    this.loadTask = function (task_id) {
        var self = this;
        $.ajax({
            url: self.root + "/diretorias",
            data: {
                mode: "load_task",
                id: task_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "<div class='task-box'>";
                    html += '<input type="button" class="ajax-close-top ajax-close-box" value="Fechar">';
                    html += "<div class='title'>" + data.task.titulo + "</div>";
                    switch (parseInt(data.task.prioridade)) {
                        case 3:
                            html += "<div class='task-prioridade-3'>Alta Prioridade</div>";
                            break;
                        case 2:
                            html += "<div class='task-prioridade-2'>Média Prioridade</div>";
                            break;
                        case 1:
                            html += "<div class='task-prioridade-1'>Baixa Prioridade</div>";
                            break;
                        default:
                            html += "<div class='task-prioridade-none'>Sem Prioridade</div>";
                            break;
                    }
                    html += "<div class='info'>";
                    if (data.task.is_owner === true) {
                        html += "<div class='task-button button-edit' task='" + data.task.id + "'>Editar Tarefa</div>";
                        if (parseInt(data.task.status) === 1)
                            html += "<div class='task-button button-archive' task='" + data.task.id + "'>Arquivar Tarefa</div>";
                        else
                            html += "<div class='task-button button-archive' task='" + data.task.id + "'>Desarquivar Tarefa</div>";
                    }

                    html += "Criado por <strong>" + data.task.usuario + "</strong> em <strong>" + data.task.data_criacao + "</strong>";
                    html += "<div class='clear'></div></div>";
                    html += "<div class='desc'>" + data.task.descricao + "</div>";
                    html += "<div id='comments-box-6-" + data.task.id + "'></div>";
                    html += "</div>";
                    loadBigAjaxBox(html);
                    self.bindChangeTaskStatus();
                    self.bindEditTask();
                    var commentsinterface = new commentsInterface();
                    commentsinterface.bindCommentsButtons();
                    commentsinterface.loadComments("#comments-box-6-" + data.task.id, 6, data.task.id);
                }
            }
        });
    };

};