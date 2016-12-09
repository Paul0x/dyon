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
    this.checkcontroller;
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

        $("#board-control-archive").die().live("click", function () {
            self.switchThreadStatusView();
        });

        $("#board-control-switch-view").die().live("click", function () {
            self.switchThreadView();
        });

        $("#add-thread").die().live("click", function () {
            self.loadAddThreadForm();
        });

        $("#board-wrap .thread").die().live("click", function () {
            self.loadThread(this, false);
        });

        $(".thread-wrap .badges .archive-thread").die().live("click", function () {
            self.loadArchiveThreadRequest(this);
        });
        $(".thread-wrap .badges .edit-thread").die().live("click", function () {
            self.loadEditThreadForm();
        });
    };

    this.loadArchiveThreadRequest = function (element) {
        var self = this;
        var threadstatus = parseInt($(element).attr("status"));
        var old_html = $(".thread-wrap .badges").html();
        var term;
        var new_status;

        if (threadstatus === 1) {
            term = "rquivar";
            new_status = 0;
        } else {
            term = "tivar";
            new_status = 1;
        }

        var html = "<div class='info'>Você tem certeza que deseja a" + term + " essa thread?</div>";
        html += "<div id='thread-archive-submit' class='control'><i class='fa fa-trash'></i> A" + term + "</div>";
        html += "<div class='control back'><i class='fa fa-times'></i> Voltar</div>";
        $(".thread-wrap .badges").html(html).addClass("controlform");

        $("#thread-archive-submit").die().live("click", function () {
            var thread_id = parseInt($(".thread-wrap").attr("threadid"));
            if (isNaN(thread_id)) {
                $(".thread-wrap .badges").html(old_html);
            }
            $.ajax({
                url: self.root + "/boards",
                data: {
                    mode: "archive_thread",
                    thread_id: thread_id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    $(".thread-wrap .badges").html(old_html).removeClass("controlform");
                    if (data.success === "true") {
                        if (new_status === 0) {
                            $(".thread-wrap .badges .archive-thread").html("<i class='fa fa-check'></i> Ativar Thread").attr("status", 0);
                        } else {
                            $(".thread-wrap .badges .archive-thread").html("<i class='fa fa-trash'></i> Arquivar Thread").attr("status", 1);

                        }
                    }
                }
            });
        });
        $(".thread-wrap .badges .back").die().live("click", function () {
            $(".thread-wrap .badges").html(old_html).removeClass("controlform");
        });


    };

    this.loadPreviousAttachments = function (attachments) {
        $("#attachment-files-list").prepend("<div class='previous-attachments'></div>");
        $.each(attachments, function (idx, attachment) {
            $("#attachment-files-list .previous-attachments").append("<div class='attachment'><i class='fa fa-file-o'></i> | " + attachment + "</div>");
        });
    };

    this.checklistForm = function () {
        var self = this;
        self.checkcontroller = new checkList();
        self.checkcontroller.init(function (obj) {
            self.threadobj.checklist = obj;
            $("#add-create-checklist").html("<b>Checklist Adicionada</b><br/> " + self.threadobj.checklist.title);
            closeAjaxBox();
        });
        if (self.threadobj.checklist) {
            self.checkcontroller.fillChecklist(self.threadobj.checklist);
        }
    };

    this.setExpiringDate = function (current_date) {
        $("#add-expiring-date").html("<input name='expiring-date' type='text' placeholder='Data de Vencimento' />").die();
        $("#add-expiring-date input").datepicker({
            dateFormat: "dd/mm/yy"
        });
        if (current_date) {
            $("#add-expiring-date input").datepicker("setDate", current_date);
        }
    };

    this.toggleStatusSystem = function () {
        var self = this;
        if (self.threadobj.statussystem) {
            $("#add-status-system").html("Progresso e Status <strong>(Desativado)</strong>");
            self.threadobj.statussystem = false;
        } else {
            $("#add-status-system").html("Progresso e Status <strong>(Ativado)</strong>");
            self.threadobj.statussystem = true;
        }
    };

    this.loadThread = function (thread, numeric) {
        var self = this;
        if (!numeric) {
            var id = parseInt($(thread).attr("id"));
        } else {
            var id = parseInt(thread);
        }
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
                    if ($("#checklist-wrap").length) {
                        self.checkcontroller_thread = new checkList();
                        self.checkcontroller_thread.loadCheckList(id, self.updateCheckList);
                    }
                    var statuscontroller = new statusController();
                    statuscontroller.loadStatusSystem(data.thread.ss, id, self.updateThreadStatus);
                } else {
                }
            }
        });
    };

    this.updateThreadStatus = function (thread_id, status) {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "update_status",
                thread_id: thread_id,
                status: status
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var statuscontroller = new statusController();
                    statuscontroller.revertStatusInterface();
                    statuscontroller.updateStatusSystem(data.thread);
                    statuscontroller.updateHistory(data.thread.ss.history);
                }
            }
        });
    };

    this.updateCheckList = function (thread_id, checklist) {
        var error_flag = false;
        var checklist_items = new Array();
        $.each(checklist, function (idx, item) {
            var item_obj = new Object();
            item_obj.id = parseInt($(item).attr("checkid"));
            item_obj.status = parseInt($(item).attr("status"));

            if (isNaN(item_obj.id)) {
                console.log("Identificador do item precisa ser numérico.");
                error_flag = true;
            }

            if (isNaN(item_obj.status) || (item_obj.status !== 1 && item_obj.status !== 0)) {
                console.log("Status do item inválido.");
                error_flag = true;
            }

            checklist_items.push(item_obj);
        });

        if (error_flag) {
            return;
        }

        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "update_checklist",
                thread_id: thread_id,
                checklist: checklist_items
            },
            success: function (data) {
                data = eval("( " + data + " )");

            }
        });
    };

    this.loadAddThreadForm = function () {
        var self = this;
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "add_thread_form"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#board-wrap .threads-wrap").html(data.html);
                    self.initThreadForm(0);
                } else {
                    self.loadBoardControlFormErrorMessage(data.error);
                }
            }
        });
    };

    this.loadEditThreadForm = function () {
        var self = this;
        var thread_id = parseInt($(".thread-wrap").attr("threadid"));
        if (isNaN(thread_id)) {
            return;
        }
        $.ajax({
            url: self.root + "/boards",
            data: {
                mode: "edit_thread_form",
                thread_id: thread_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    self.threadobj = new Object();
                    $("#board-wrap .threads-wrap").html(data.html);
                    self.initThreadForm(1);
                    if (data.thread.ss) {
                        self.toggleStatusSystem();
                    }
                    if (data.thread.expiring_date) {
                        self.setExpiringDate(data.thread.expiring_date);
                    }
                    if (data.thread.checklist) {
                        self.threadobj.checklist = data.thread.checklist;
                        $("#add-create-checklist").html("<b>Checklist Adicionada</b><br/> " + self.threadobj.checklist.title);
                    }
                    if (data.thread.attachments) {
                        self.loadPreviousAttachments(data.thread.attachments);
                    }
                } else {
                    self.loadBoardControlFormErrorMessage(data.error);
                }
            }
        });
    };

    this.initThreadForm = function (type) {
        var self = this;
        $(".thread-form .add-more-files").die().live("click", function () {
            self.addMoreLinksThreadForm();
        });
        self.threadobj = new Object();
        self.loadBackButton();

        $("#add-create-checklist").die().live("click", function () {
            self.checklistForm();
        });

        $("#add-expiring-date").die().live("click", function () {
            self.setExpiringDate();
        });

        $("#add-status-system").die().live("click", function () {
            self.toggleStatusSystem();
        });

        if (type === 0) {
            $("#add-thread-submit").die().live("click", function () {
                self.submitThreadForm(0);
            });
        } else {
            $("#edit-thread-submit").die().live("click", function () {
                self.submitThreadForm(1);
            });

        }
    };

    this.submitThreadForm = function (action) {
        var self = this;
        if (self.threadobj === undefined || self.threadobj === null) {
            return;
        }

        self.threadobj.title = $("input[name=title]").val();
        self.threadobj.post = $("textarea[name=post]").val();
        self.threadobj.priority = $("select[name=priority]").val();
        self.threadobj.type = $("select[name=type]").val();


        if ($("#add-expiring-date input").length) {
            self.threadobj.expiring_date = $("#add-expiring-date input[name='expiring-date']").val();
            if (self.threadobj.expiring_date === "" && action === 1) {
                self.threadobj.expiring_date = 'remove';
            }
        }

        var form = new FormData();
        var xhr = new XMLHttpRequest();
        $("input[name='attachment[]']").each(function (idx, file) {
            if (file.files[0] !== undefined) {
                form.append("attachment-file-" + idx, file.files[0]);
            }
        });

        if (action === 0) {
            form.append("mode", "add_thread");
        } else {
            form.append("mode", "edit_thread");
            self.threadobj.id = parseInt($("input[name=id]").val());
            if (isNaN(self.threadobj.id)) {
                return;
            }
            form.append("id", self.threadobj.id);
        }
        form.append("majax", "true");
        form.append("thread[title]", self.threadobj.title);
        form.append("thread[post]", self.threadobj.post);
        form.append("thread[priority]", self.threadobj.priority);
        form.append("thread[type]", self.threadobj.type);
        if (self.threadobj.expiring_date) {
            form.append("thread[expiring_date]", self.threadobj.expiring_date);
        }
        if (self.threadobj.checklist) {
            form.append("thread_checklist", JSON.stringify(self.threadobj.checklist));
        }
        if (self.threadobj.statussystem === true) {
            form.append("thread[statussystem]", true);
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
                    self.loadThread(data.thread_id, true);
                } else {
                    self.loadThreadFormError(data.error);
                }
            }
        };
        xhr.send(form);
    };

    this.loadThreadFormError = function (message) {
        $(".thread-form .error-wrap").html("<div class='error'>" + message + "</div>");

    };

    this.loadBackButton = function () {
        var self = this;
        if (!$("#thread-add-backbutton").length) {
            $("#add-thread").css("display", "none");
            $(".board-header .control-right").css("display", "none");
            $("#add-thread").after("<div class='control' id='thread-add-backbutton'>Voltar</div>");
            $("#thread-add-backbutton").die().live("click", function () {
                var thread_id = parseInt($("input[name=id]").val());
                if (!isNaN(thread_id) && $(".thread-form").length) {
                    self.loadThread(thread_id, true);
                } else {
                    self.loadUserBoard();
                }
                $(".board-header .control-right").css("display", "block");
                $("#add-thread").css("display", "block");
                $(this).remove();
                if (self.threadobj) {
                    self.threadobj = null;
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