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
 *  File: comments.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

commentsInterface = function () {
    this.root = $("#dir-root").val();
    

    this.bindCommentsButtons = function () {
        this.bindSubmitComment();
        this.bindDeleteComment();
        this.bindEditComment();
    };

    this.bindSubmitComment = function () {
        var self = this;
        $(document).off("click", ".comment-button-submit").on("click", ".comment-button-submit", function () {
            var button = this;
            var node = $(this).attr("node");
            var node_id = $(this).attr("node-id");
            var text = $("#comments-text-" + node + "-" + node_id).val();

            if (isNaN(parseInt(node)) || isNaN(parseInt(node_id))) {
                self.messageError("Não é possível enviar mensagem para o conteúdo desejado.", node_id);
                return;
            }
            if (text === "") {
                self.messageError("O conteúdo do texto não pode estar vazio.", node_id);
                return;
            }
            $(this).val("Aguarde...").attr("disabled", "disabled");
            $.ajax({
                url: self.root + "/manager/comentarios",
                data: {
                    mode: "sendComment",
                    node: node,
                    node_id: node_id,
                    text: text
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        var html = self.loadBody(data.comment);
                        $(button).val("Comentar").removeAttr("disabled");
                        $("#comments-box-" + node + "-" + node_id + " .comments-header").after(html);
                        $(".comments-header[node-id=" + node_id + "] .message-error-wrap").html("");
                        
                        if($("#comments-box-" + node + "-" + node_id + " .comments-error").length !== 0)
                        {
                            $("#comments-box-" + node + "-" + node_id + " .comments-error").remove();
                        }
                        $("#comments-text-" + node + "-" + node_id).val("");
                    } else {
                        self.messageError(data.error, node_id);
                        $(button).val("Comentar").removeAttr("disabled");
                    }
                }
            });
        });
    };

    this.bindDeleteComment = function () {
        var self = this;
        $(document).off("click", ".comment-delete").on("click", ".comment-delete", function () {
            var comment = $(this).attr("comment");
            var node_id = $(this).attr("node-id");
            if (isNaN(parseInt(comment))) {
                this.messageError("Identificador do comentário inválido.");
                return;
            }
            $.ajax({
                url: self.root + "/manager/comentarios",
                data: {
                    mode: "deleteComment",
                    comment: comment
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        $(".comment-body[comment=" + comment + "]").remove();
                    } else {
                        self.messageError(data.error, node_id);
                    }
                }
            });
        });
    };

    this.bindEditComment = function () {
        var self = this;
        $(document).off("click", ".comment-edit").on("click", ".comment-edit", function () {
            var comment = $(this).attr("comment");
            var node_id = $(this).attr("node-id");
            var button = this;
            if (parseInt($(this).attr("step")) === 1) {
                if (isNaN(parseInt(comment))) {
                    this.messageError("Identificador do comentário inválido.");
                    return;
                }
                var regex = /<br[^>]*>/gi;
                var comment_text = $(".comment-body[comment=" + comment + "] .comment-text").html().replace(regex,"");
                $(".comment-body[comment=" + comment + "] .comment-text").html("<textarea class='edit-comment-textarea' comment='" + comment + "'>" + comment_text + "</textarea>");
                $(button).html("Finalizar Edição").attr("step", 2);
            } else if (parseInt($(this).attr("step")) === 2) {
                var texto = $(".edit-comment-textarea[comment=" + comment + "]").val();
                $.ajax({
                    url: self.root + "/manager/comentarios",
                    data: {
                        mode: "editComment",
                        comment: comment,
                        text: texto
                    },
                    success: function (data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            $(".comment-body[comment=" + data.comment.id + "] .comment-text").html(data.comment.texto);
                            $(button).html("Editar").attr("step", 1);
                        } else {
                            self.messageError(data.error, node_id);
                        }
                    }
                });
            }
        });
    };

    this.loadComments = function (box, node, node_id) {
        var self = this;
        node = parseInt(node);
        node_id = parseInt(node_id);
        if (isNaN(node) || isNaN(node_id))
            return;
        $(box).html(this.loadHeader(node, node_id));
        var html = "";
        $.ajax({
            url: self.root + "/manager/comentarios",
            data: {
                mode: "loadComments",
                node: node,
                node_id: node_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $.each(data.comments, function (index, comment) {
                        html += self.loadBody(comment);
                    });
                } else {
                    html = "<div class='comments-error'>Nenhum comentário encontrado.</div>";
                }

                $(box).append(html);
            }
        });
    };

    this.loadHeader = function (node, node_id) {
        node = parseInt(node);
        node_id = parseInt(node_id);
        if (isNaN(node) || isNaN(node_id))
            return;

        var html = "<div class='comments-header' node-id='" + node_id + "'>";
        html += "<textarea class='comments-text' id='comments-text-" + node + "-" + node_id + "' placeholder='Digite seu comentário.'></textarea>";
        html += "<input type='button' class='comment-button-submit' value='Comentar' node='" + node + "' node-id='" + node_id + "' />";
        html += "<div class='message-error-wrap'></div>";
        html += "</div>";
        return html;
    };

    this.loadBody = function (comment) {
        var self = this;
        var html = "<div class='comment-body' comment='" + comment.id + "'>";
        html += "<div class='comment-header'></div>";
        html += "<div class='comment-left'><div class='comment-title'>" + comment.nome + " </div><img class='comment-avatar' src='" + self.root + "/images/avatar/" + comment.image + "'/><div class='comment-date'>" + comment.data_criacao + "</div></div>";
        html += "<div class='comment-text'>" + comment.texto + "</div>";
        html += "<div class='clear'></div>";
        if (comment.buttons === "true") {
            html += "<div class='comment-footer'>";
            html += "<div class='comment-edit' comment='" + comment.id + "' node-id='" + comment.id_node + "' step='1'>Editar</div><div class='comment-delete' comment='" + comment.id + "' node-id='" + comment.id_node + "'>Deletar</div>";
            html += "</div>";
        }
        html += "</div>";

        return html;
    };

    this.messageError = function (message, node) {
        $(".comments-header[node-id=" + node + "] .message-error-wrap").html("<div class='comments-error'>" + message + "</div>");
    };

    this.countComments = function (node_type, node_id, countCallback) {
        var self = this;
        $.ajax({
            url: self.root + "/manager/comentarios",
            data: {
                mode: "countComments",
                node: node_type,
                node_id: node_id
            },
            success: countCallback
        });
    };

};