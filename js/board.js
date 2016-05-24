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
    };

    this.loadBoardsBoxes = function () {
        var self = this;
        $.ajax({
            url: self.root + "/diretorias",
            data: {
                mode: "load_boards_boxes"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $.each(data.diretorias, function (index, diretoria) {
                        $("#board-list-wrap .items").append("<div class='item' diretoria='" + diretoria.id + "'>" + diretoria.nome + "</div>");
                    });
                    self.bindBoardSelection();
                }
            }
        });
    };

    this.loadUserBoard = function () {
        var self = this;
        $.ajax({
            url: self.root + "/diretorias",
            data: {
                mode: "load_user_board"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "<div class='container' board='" + data.board.id + "'>";
                    html += "<div class='title'>" + data.board.nome + "</div>";
                    html += "<div class='controllers'></div>";
                    html += "<div class='tasks'><div class='subtitle'>Tarefas</div>";
                    if (data.board.is_member === true) {
                        html += "<div class='task-add-button' board='" + data.board.id + "'>Add Tarefa</div>";
                    }
                    html += "<section></section></div>";
                    html += "<div class='members'><div class='subtitle'>Integrantes</div><section></section></div>";
                    html += "<div class='clear'></div>";
                    html += "</div>";
                    $("#board-wrap").html(html);
                    self.loadBoardTasks(data.board.id);
                    self.loadBoardMembers(data.board.id);
                    if (data.board.is_member === true) {
                        self.bindAddTask(data.board.id);
                    }
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
        $("#board-list-wrap .item").die().live("click", function () {
            var id = $(this).attr("diretoria");
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

    this.loadBoardTasks = function (board_id) {
        var self = this;
        $.ajax({
            url: self.root + "/diretorias",
            data: {
                mode: "load_board_tasks",
                id: board_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "";
                    $.each(data.tasks, function (index, task) {
                        html += "<div class='task' task='" + task.id + "'>";
                        html += "<div class='prioridade pri-" + task.prioridade + "'>";
                        if (task.data_vencimento !== null) {
                            html += "Vencimento em " + task.data_vencimento;
                        }
                        html += "</div>";
                        html += "<div class='main'>";
                        html += task.titulo;
                        html += "<div class='date'>" + task.data_criacao + "</div>";
                        html += "</div>";
                        html += "<summary>" + task.descricao + "</summary>";
                        html += "</div>";
                        if ((index + 1) % 3 === 0) {
                            html += "<div class='clear'></div>";
                        }
                    });

                    $("#board-wrap .tasks section").html(html);
                    $("#board-wrap .tasks .task").die().live("click", function () {
                        self.loadTask($(this).attr("task"));
                    });
                }
            }
        });
    };

    this.loadBoardMembers = function (board_id) {
        var self = this;
        $.ajax({
            url: self.root + "/diretorias",
            data: {
                mode: "load_board_members",
                id: board_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var html = "";
                    $.each(data.users, function (index, member) {
                        html += "<a href='" + self.root + "/cliente/" + member.id + "'><div class='member'>" + member.nome + "</div></a>";
                    });
                    $("#board-wrap .members section").html(html);
                }
            }
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

    this.bindChangeTaskStatus = function () {
        var self = this;
        $(".button-archive").die().live("click", function () {
            var id = parseInt($(this).attr('task'));
            $.ajax({
                url: self.root + "/diretorias",
                data: {
                    mode: "change_task_status",
                    id: id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        console.log("changed");
                    }
                }
            });
        });
    };

    this.bindEditTask = function () {
        var self = this;
        $(".button-edit").die().live("click", function () {
            var id = parseInt($(this).attr('task'));
            $.ajax({
                url: self.root + "/diretorias",
                data: {
                    mode: "load_task",
                    id: id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var vencimento_string;
                        if (data.task.data_vencimento_uf !== null) {
                            vencimento_string = data.task.data_vencimento_uf.split(" ");
                            vencimento_string = vencimento_string[0].split("-");
                            vencimento_string = vencimento_string[2] + "/" + vencimento_string[1] + "/" + vencimento_string[0];
                        } else
                        {
                            vencimento_string = "";
                        }

                        var html = "<div class='ajax-box-title'>Editar Tarefa</div>";
                        html += "<div id='task-edit-form'>";
                        html += "<input type='hidden' name='task-id' value='" + data.task.id + "' />";
                        html += "<div class='item'>";
                        html += "<label>Título</label>";
                        html += "<input type='text' value='"+data.task.titulo+"' name='task-titulo' placeholder='Título' />";
                        html += "</div>";
                        html += "<div class='item'>";
                        html += "<label>Prioridade</label>";
                        html += "<select name='task-prioridade'>";
                        for (var i = 0; i <= 3; i++) {
                            html += "<option value='" + i + "' ";
                            if (parseInt(data.task.prioridade) === i)
                                html += " selected";
                            html += ">";
                            switch (parseInt(i)) {
                                case 3:
                                    html += "Alta Prioridade";
                                    break;
                                case 2:
                                    html += "Média Prioridade";
                                    break;
                                case 1:
                                    html += "Baixa Prioridade";
                                    break;
                                default:
                                    html += "Sem Prioridade";
                                    break;
                            }
                            html += "</option>";
                        }
                        html += "</select>";
                        html += "</div>";
                        html += "<div class='item'>";
                        html += "<label>Vencimento</label>";
                        html += "<input type='date' value='' name='task-vencimento' placeholder='Vencimento (Opcional)' />";
                        html += "</div>";
                        html += "<div class='item'>";
                        html += "<label>Descrição</label>";
                        html += "<textarea name='task-desc'>" + data.task.descricao_nl + "</textarea>";
                        html += "</div>";
                        html += "<input type='button' class='btn-01' value='Editar' name='submit' id='task-edit-button' />";
                        html += "<input type='button' class='btn-03 ajax-close-box' value='Fechar' name='submit' id='task-edit-button' />";
                        html += "</div>";
                        loadAjaxBox(html);
                        $("input[name='task-vencimento']").mask("99/99/9999").val(vencimento_string);
                        $("#task-edit-button").die().live("click", function () {
                            var id = parseInt($("input[name='task-id']").val());
                            var titulo = $("input[name='task-titulo']").val();
                            var vencimento = $("input[name='task-vencimento']").val();
                            var prioridade = $("select[name='task-prioridade']").val();
                            var desc = $("textarea[name='task-desc']").val();
                            $.ajax({
                                url: self.root + "/diretorias",
                                data: {
                                    mode: "board_edit_task",
                                    titulo: titulo,
                                    vencimento: vencimento,
                                    prioridade: prioridade,
                                    desc: desc,
                                    id: id
                                },
                                success: function (data) {
                                    data = eval("( " + data + " )");
                                    if (data.success === "true") {
                                        self.loadTask(data.task);
                                    }
                                }
                            });
                        });
                    }
                }
            });
        });
    };
};