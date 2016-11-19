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
        self.teste = "ayldamao";
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

        $("#board-control-archive").die().live("click", function () {
            self.switchThreadStatusView();
        });

        $("#board-control-switch-view").die().live("click", function () {
            self.switchThreadView();
        });

        $("#add-thread").die().live("click", function () {
            self.loadAddThreadForm(0);
        });

        $("#board-wrap .thread").die().live("click", function () {
            self.loadThread(this);
        });
    };

    this.createCheckList = function () {
        var self = this;
        var checkcontroller = new checkList();
        checkcontroller.init(function (obj) {
            self.newthread.checklist = obj;
            $("#add-create-checklist").html("<b>Checklist Adicionada</b><br/> " + self.newthread.checklist.title);
            closeAjaxBox();
        });
    };

    this.setExpiringDate = function () {
        $("#add-expiring-date").html("<input name='expiring-date' type='text' placeholder='Data de Vencimento' />").die();
        $("#add-expiring-date input").datepicker({
            dateFormat: "dd/mm/yy"
        });
    };

    this.toggleStatusSystem = function () {
        var self = this;
        if (self.newthread.statussystem) {
            $("#add-status-system").html("Progresso e Status <strong>(Desativado)</strong>");
            self.newthread.statussystem = false;
        } else {
            $("#add-status-system").html("Progresso e Status <strong>(Ativado)</strong>");
            self.newthread.statussystem = true;
        }
    };

    this.loadThread = function (thread) {
        var self = this;
        var id = parseInt($(thread).attr("id"));
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "load_thread",
                id: id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-wrap .threads-wrap").html(data.html);
                    self.loadBackButton();
                } else {
                }
            }
        });
    };

    this.loadAddThreadForm = function (thread_type) {
        var self = this;
        if (thread_type !== 1 && thread_type !== 0) {
            return;
        }

        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "add_thread_form",
                thread_type: thread_type
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-wrap .threads-wrap").html(data.html);
                    self.initAddThreadForm();
                } else {
                    self.loadBoardControlFormErrorMessage(data.error);
                }
            }
        });
    };

    this.initAddThreadForm = function () {
        var self = this;
        $(".add-thread-form .add-more-files").die().live("click", function () {
            self.addMoreLinksThreadForm();
        });
        self.newthread = new Object();
        self.loadBackButton();

        $("#add-create-checklist").die().live("click", function () {
            self.createCheckList();
        });

        $("#add-expiring-date").die().live("click", function () {
            self.setExpiringDate();
        });

        $("#add-status-system").die().live("click", function () {
            self.toggleStatusSystem();
        });

        $("#add-thread-submit").die().live("click", function () {
            self.addThread();
        });
    };

    this.addThread = function () {
        var self = this;
        if (self.newthread === undefined || self.newthread === null) {
            return;
        }

        self.newthread.title = $("input[name=title]").val();
        self.newthread.post = $("textarea[name=post]").val();
        self.newthread.priority = $("select[name=priority]").val();
        self.newthread.type = $("select[name=type]").val();


        if ($("#add-expiring-date input").length) {
            self.newthread.expiring_date = $("#add-expiring-date input[name='expiring-date']").val();
        }

        var form = new FormData();
        var xhr = new XMLHttpRequest();
        $("input[name='attachment[]']").each(function (idx, file) {
            if (file.files[0] !== undefined) {
                form.append("attachment-file-" + idx, file.files[0]);
            }
        });
        form.append("mode", "add_thread");
        form.append("majax", "true");
        form.append("thread[title]", self.newthread.title);
        form.append("thread[post]", self.newthread.post);
        form.append("thread[priority]", self.newthread.priority);
        form.append("thread[type]", self.newthread.type);
        if (self.newthread.expiring_date) {
            form.append("thread[expiring_date]", self.newthread.expiring_date);
        }
        if (self.newthread.checklist) {
            form.append("thread_checklist", JSON.stringify(self.newthread.checklist));
        }
        xhr.open('POST', self.root + "/boards", true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            /*if (xhr.readyState == 2) {
             $("#add-thread-submit").val("Carregando...").attr("disabled", "disabled");
             }*/
            if (xhr.readyState == 4 && xhr.status == 200) {
                $("#confirm-pacote-submit").val("Confirmar Parcela").removeAttr("disabled");
                var data = eval("(" + xhr.responseText + ")");
                if (data.success == "true") {
                    self.loadThread(data.thread_id);
                }
            }
        };
        xhr.send(form);



    };

    this.loadBackButton = function () {
        var self = this;
        if (!$("#thread-add-backbutton").length) {
            $("#add-thread").css("display", "none");
            $(".board-header .control-right").css("display", "none");
            $("#add-thread").after("<div class='control' id='thread-add-backbutton'>Voltar</div>");
            $("#thread-add-backbutton").die().live("click", function () {
                self.loadUserBoard();
                $(".board-header .control-right").css("display", "block");
                $("#add-thread").css("display", "block");
                $(this).remove();
                if (self.newthread) {
                    self.newthread = null;
                }
            });
        }
    };

    this.addMoreLinksThreadForm = function () {
        var self = this;
        $("#attachment-files-list").append("<input type='file' name='attachment[]' />");
        var num_files = $("#attachment-files-list input").length;
        if (num_files >= 10) {
            $(".add-thread-form .add-more-files").die().html("LIMITE DE ARQUIVOS POR THREAD ALCANÇADO");
        }
    };

    this.updateThreadObjects = function () {
        var self = this;
        self.viewstyle = 'list';
        self.threads_list = $(".threads-wrap .thread");
        self.threads_column = new Object();
        self.threads_column.tasks = new Array();
        self.threads_column.discussions = new Array();
        $.each(self.threads_list, function (idx, thread) {
            if (parseInt($(thread).attr("tipo")) === 0) {
                self.threads_column.discussions.push(thread);
            } else {
                self.threads_column.tasks.push(thread);
            }
        });
    };

    this.switchThreadView = function () {
        var self = this;
        if (self.viewstyle === undefined || self.viewstyle === 'list') {
            self.viewstyle = 'column';
            $("#board-control-switch-view").html('<i class="fa fa-list-alt" aria-hidden="true"></i>').attr("title", "Visualizar por Lista");
            var html = "<div class='column-50 discussion-threads-wrap'>";
            html += "<div class='title'>Discussões</div>";
            if (self.threads_column.discussions.length > 0) {
                $.each(self.threads_column.discussions, function (idx, thread) {
                    html += thread.outerHTML;
                });
            } else {
                html += "<div class='no-thread'>Nenhuma Thread Encontrada</div>";
            }
            html += "</div>";
            html += "<div class='column-50 task-threads-wrap'>";
            html += "<div class='title'>Tarefas</div>";
            if (self.threads_column.tasks.length > 0) {
                $.each(self.threads_column.tasks, function (idx, thread) {
                    html += thread.outerHTML;
                });
            } else {
                html += "<div class='no-thread'>Nenhuma Thread Encontrada</div>";
            }
            html += "</div>";
            $(".threads-wrap").html(html);
        } else {
            self.viewstyle = 'list';
            $("#board-control-switch-view").html('<i class="fa fa-columns" aria-hidden="true"></i>').attr("title", "Visualizar por Tipo");
            var html = "";
            if (self.threads_list.length > 0) {
                $.each(self.threads_list, function (idx, thread) {
                    html += thread.outerHTML;
                });
            } else {
                html += "<div class='no-thread'>Nenhuma Thread Encontrada</div>";
            }
            $(".threads-wrap").html(html);

        }




    };

    this.switchThreadStatusView = function () {
        var self = this;
        var board_id = $("#board-id-ref").val();
        if (self.statusview === undefined || self.statusview === 1) {
            self.statusview = 0;
            $("#board-control-archive").html('<i class="fa fa-check" aria-hidden="true"></i>').attr("title", "Ver Ativos");
        } else {
            self.statusview = 1;
            $("#board-control-archive").html('<i class="fa fa-trash" aria-hidden="true"></i>').attr("title", "Ver Arquivo");
        }

        self.loadBoardThreads(board_id, self.statusview);

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
        html += "<input type='button' id='board-rename-form-submit' class='btn-01' value='Renomear'/>";
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
                    sideMenuHeightFix();
                    self.updateThreadObjects();

                }
            }
        });
    };

    this.loadBoardThreads = function (board_id, status) {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "load_boad_threads",
                board_id: board_id,
                status: status
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-wrap .threads-wrap").html(data.html);
                    self.updateThreadObjects();
                } else {
                    if (status === 1) {
                        $("#board-wrap .threads-wrap").html("<div class='no-thread'>Nenhuma Thread Encontrada</div>");
                    } else {
                        $("#board-wrap .threads-wrap").html("<div class='no-thread'>Nenhuma Thread Arquivada</div>");

                    }
                    self.updateThreadObjects();
                }
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

};