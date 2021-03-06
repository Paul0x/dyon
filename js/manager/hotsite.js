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
 *  File: hotsite.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

hotsiteInterface = function() {
    var loaded_hotsite;
    var loaded_page;
    var loaded_blocks;
    var block_delimiters;
    var content_show;

    this.root = $("#dir-root").val();
    this.init = function(id) {
        var self = this;
        self.loadHotsiteInterface();
    };

    this.loadHotsiteInterface = function() {
        var self = this;
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_hotsite_interface"
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#topbar-menu-hotsite .menu-wrap").html(data.modules.topmenu);
                    self.bindMenuController();
                    self.loadPageHotsiteInterface();
                    self.bindBlockController();
                    self.loaded_hotsite = data.hotsite.id;
                    self.block_delimiters = true;
                    self.content_show = true;
                }
            }
        });
    };

    this.bindMenuController = function() {
        var self = this;
        $("#hotsite-administrative-topmenu .item[ref=config]").bind("click", function() {
            self.loadHotsiteConfigInterface();
        });
        $("#viewcontroller-block-delimiter").bind("click", function() {
            self.toggleBlockBorders();
        });
    };

    this.toggleBlockBorders = function() {
        var self = this;
        if (self.block_delimiters) {

            $("#preview-hotsite .block").each(function() {
                var width = $(this).attr("block-width");
                $(this).width(width + "%");
            });
            $("#preview-hotsite .block").css("border", "none");
            $("#preview-hotsite .block").css("margin", "0px");
            $("#preview-hotsite .block").css("padding", "0px");
            $("#viewcontroller-block-delimiter span").removeClass("fa-check-square-o").addClass("fa-square-o");
            self.block_delimiters = false;
        } else {
            $("#preview-hotsite .block").each(function() {
                var width = $(this).attr("block-width");
                $(this).width("calc(" + width + "% - 10px");
            });
            $("#preview-hotsite .block").css("border", "1px dashed #018D3C");
            $("#preview-hotsite .block").css("margin", "5px");
            $("#preview-hotsite .block").css("padding", "5px");
            $("#viewcontroller-block-delimiter span").removeClass("fa-square-o").addClass("fa-check-square-o");
            self.block_delimiters = true;

        }

    };

    this.loadPageHotsiteInterface = function(page) {
        var self = this;
        if (page === undefined) {
            page = 1;
        }
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_hotsite_page",
                page: page
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    self.loaded_page = data.page.id;
                    self.loadSideMenu(data.page.sidemenu);
                    self.renderPreview(data.page.render);
                    self.loaded_blocks = data.page.blocks;
                    self.loadBlocks();
                    self.drag = dragula([document.getElementById("page-" + self.loaded_hotsite + "-" + self.loaded_page)], {
                        moves: function(el, container, handle) {
                            return handle.className === 'fa fa-arrows move';
                        }
                    });
                    self.drag.on('drop', function()
                    {
                        self.updateBlockWeights(page);
                    });
                }
            }
        });
    };

    this.updateBlockWeights = function(page) {
        var self = this;
        var block_weights = new Array();
        var i = 0;
        $(".block").each(function() {
            block_weights[i] = (parseInt($(this).attr("rel")));
            i++;
        });

        if (isNaN(page)) {
            return;
        }

        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "update_block_weight",
                page: page,
                blocks: block_weights
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {

                }
            }
        });



    };

    this.loadBlocks = function() {
        var color_pattern = /^[0-9A-F]{6}$/;
        var self = this;
        if (self.loaded_blocks === undefined) {
            return;
        }

        $.each(self.loaded_blocks, function(index, block) {
            var html = "<div class='block' id='hotsite-block-" + block.id + "' rel='" + block.id + "' block-width='" + block.width + "'>";
            html += "<div class='block-controller' rel='" + block.id + "'>";
            html += "<i class=\"fa fa-arrows move\" aria-hidden=\"true\"></i>";
            html += "<i class=\"fa fa-pencil edit\" aria-hidden=\"true\"></i>";
            html += "</div>";
            html += "</div>";

            var css = "#hotsite-block-" + block.id + " { \n\
                        width: calc(" + block.width + "% - 10px);\n\
                        min-height: 50px;\n\
                        margin: 5px;\n\
                        padding: 5px;";
            if (block.background_color !== 0 && color_pattern.test(block.background_color)) {
                css += "background-color: #" + block.background_color + ";";
            }
            if (block.background_image) {
                css += "background-image: url(/dyon/hotsite/block_image/" + block.background_image + ");";
            }
            if (parseInt(block.background_image_repeat) === 1) {
                css += "background-repeat: repeat;";
            } else {
                css += "background-repeat: no-repeat;";

            }
            css += "}";

            $("#preview-hotsite style").append(css);
            $("#preview-hotsite .hotsite-page").append(html);
            self.loadContentByBlock(block.id);
        });

        if (!self.block_delimiters) {
            self.block_delimiters = true;
            self.toggleBlockBorders();
        }
    };

    this.loadContentByBlock = function(id) {
        var self = this;
        if (isNaN(id) || $("#hotsite-block-" + id).length === 0) {
            return;
        }

        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "load_block_content",
                id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $.each(data.contents, function(index, content) {
                        var html = "<div class='content-preview-wrap' content='" + content.id + "'><div class='content-preview-edit fa fa-cog' content='" + content.id + "'></div>" + content.render + "</div>";
                        $("#hotsite-block-" + id).append(html);
                    });

                }
            }
        });

    };

    this.bindBlockController = function() {
        var self = this;
        $(".block").live("mouseover", function() {
            if (self.block_delimiters) {
                var id = $(this).attr("rel");
                $(".block-controller").css("display", "none");
                $(".block-controller[rel=" + id + "]").css("display", "block");
            }
        });
        $(".block").live("mouseout, mouseleave", function() {
            if (self.block_delimiters) {
                var id = $(this).attr("rel");
                $(".block-controller[rel=" + id + "]").css("display", "none");
            }
        });
        $(".content-preview-wrap").live("mouseover", function() {
            if (self.content_show) {
                var id = $(this).attr("content");
                $(".content-preview-edit").css("display", "none");
                $(".content-preview-edit[content=" + id + "]").css("display", "block");
            }
        });
        $(".content-preview-wrap").live("mouseout, mouseleave", function() {
            if (self.content_show) {
                var id = $(this).attr("content");
                $(".content-preview-edit[content=" + id + "]").css("display", "none");
            }
        });
        $(".block-controller .edit").die("click").live("click", function() {
            var id = $(this).parent().attr("rel");
            self.loadBlockEditInterface(id);
        });
        $("#hotsite-block-remove-submit").die().live("click", function() {
            var id = parseInt($(this).parent().attr("block"));
            self.loadBlockRemoveInterface(id, 0);
        });
        $("#hotsite-block-edit-submit").die().live("click", function() {
            var id = parseInt($(this).parent().attr("block"));
            self.submitBlockEdit(id);
        });
        $(".content-preview-edit").die().live("click", function() {
            var id = parseInt($(this).attr("content"));
            self.loadContentEditInterface(id);

        });
    };

    this.loadBlockRemoveInterface = function(id, step) {
        var self = this;
        switch (step) {
            case 0:
                var old_html = $("#hotsite-ajax-box-wrap").html();
                var html = "<div class='headerbox'>";
                html += "<div class='title'>Remover Bloco</div>";
                html += "<div class='info'>Ao remover o bloco você também deletará todo o conteúdo dentro dele, para preservar o conteúdo transfira ele para outro bloco.<br/> <b>Essa ação não pode ser desfeita</b>.</div>";
                html += "</div>";
                html += "<input type='button' id='remove-block-submit-button' class='hotsite-ajax-confirm-button' value='Remover Bloco' />";
                html += "<input type='button' id='remove-block-return-button' class='hotsite-ajax-return-button' value='Voltar' />";
                $("#hotsite-ajax-box-wrap").html(html);
                $("#remove-block-submit-button").die().live("click", function() {
                    self.loadBlockRemoveInterface(id, 1);
                });
                $("#remove-block-return-button").live("click", function() {
                    $(this).die();
                    $("#hotsite-ajax-box-wrap").html(old_html);
                });
                break;
            case 1:
                if (isNaN(id)) {
                    $("#remove-block-return-button").append("<div class='error'>Não é possível remover o bloco específicado</div>");
                }
                $.ajax({
                    url: self.root + "/interface/ajax",
                    data: {
                        mode: "remove_block",
                        id: id
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            closeAjaxBox();
                            self.loadPageHotsiteInterface(self.loaded_page);
                        }
                    }
                });
                break;
        }

    };

    this.loadBlockEditInterface = function(id) {
        var self = this;
        id = parseInt(id);
        if (isNaN(id)) {
            return;
        }

        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_block_edit_form",
                id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var infos = data.block;
                    loadAjaxBox(data.html);
                    var pickers = new Array();
                    $("#hotsite-blockedit-form .color-value").each(function(index, element) {
                        var field = $(this).attr("var");
                        pickers[field] = new jscolor(element, {onFineChange: ' $("#hotsite-blockedit-form input[name=background-color-none]").attr("checked", false); '});
                        if (infos[field] !== null) {
                            pickers[field].fromString(infos[field]);
                        } else {
                            pickers[field].fromString("ffffff");
                        }
                    });
                    if (infos.background_color === "0") {
                        $("#hotsite-blockedit-form input[name=background-color-none]").attr("checked", true);
                    }
                    $("#hotsite-blockedit-form .item[ref=width] select option[value=" + infos.width + "]").attr("selected", true);

                    if (parseInt(infos.background_image_repeat) === 1) {
                        $("#hotsite-blockedit-form .item[ref=background-image] input[name=background-image-repeat]").attr("checked", true);
                    }


                    if (infos.background_image !== "" && infos.background_image !== null) {
                        var img = "<img src='/dyon/hotsite/block_image/" + infos.background_image + "' width='350'";
                        if (infos.background_repeat === "true") {
                            img += " repeat";
                        }
                        img += "/>";
                        $("#hotsite-blockedit-form .item[ref=background-image] .image-value").html(img);
                        $("#hotsite-blockedit-form .item[ref=background-image] .image-value").after("<input type='checkbox' name='background-image-remove'/> Remover Imagem");
                    } else {
                        var img = "Sem Imagem";
                        $("#hotsite-blockedit-form .item[ref=background-image] .image-value").html(img);
                    }

                    self.blockEditInfo = infos;
                } else {

                }
            }
        });
    };

    this.submitBlockEdit = function(id) {
        if (isNaN(id)) {
            return;
        }
        var self = this;
        var infos = self.blockEditInfo;
        var color_pattern = /^[0-9A-F]{6}$/;
        var new_infos = new Object();
        self.hotsiteConfigError("clear");
        new_infos.background_color = $("#hotsite-blockedit-form .item[ref=background-color] .value").html();
        if ($("#hotsite-blockedit-form .item[ref=background-color] input[name=background-color-none]").is(":checked")) {
            new_infos.background_color = 'remove';
        }
        new_infos.width = $("#hotsite-blockedit-form .item[ref=width] .value").val();
        new_infos.background_repeat = $("#hotsite-blockedit-form .item[ref=background-image] input[name=background-image-repeat]").is(":checked");
        if (!color_pattern.test(new_infos.background_color) && !new_infos.background_color === null) {
            self.hotsiteConfigError("A cor do fundo está em formato inválido.");
            return;
        }

        if ($("#hotsite-background-image-file").val().length > 0) {
            new_infos.background_image = new Object();
            new_infos.background_image.file = document.getElementById("hotsite-background-image-file").files[0];
            new_infos.background_image.filename = $("#hotsite-background-image-file").val();
            new_infos.background_image.extension = new_infos.background_image.filename.substr(new_infos.background_image.filename.length - 3, 3).toLowerCase();
            if (new_infos.background_image.extension !== "jpg" && new_infos.background_image.extension !== "png" && new_infos.background_image.extension !== "gif") {
                self.hotsiteConfigError("A imagem que você enviou está em formato inválido.");
                return;
            }
        }

        if ($("#hotsite-blockedit-form .item[ref=background-image] input[name=background-image-remove]").is(":checked")) {
            new_infos.background_remove = 'remove';
        }

        var form = new FormData();
        var xhr = new XMLHttpRequest();
        form.append("id", id);
        form.append("width", new_infos.width);
        form.append("mode", "submit_block_edit");
        if (new_infos.width !== infos.background_color) {
            form.append("background_color", new_infos.background_color);
        }
        if (new_infos.background_color !== infos.background_color) {
            form.append("background_color", new_infos.background_color);
        }
        if (new_infos.background_image) {
            form.append("background_image", new_infos.background_image.file);
            form.append("background_image_filename", new_infos.background_image.filename);
        }
        if (new_infos.background_repeat) {
            form.append("background_repeat", 1);
        } else {
            form.append("background_repeat", 0);
        }
        if (new_infos.background_remove) {
            form.append("background_image_remove", true);
        }
        xhr.open('POST', self.root + "/interface/ajax", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 2) {
            }
            if (xhr.readyState === 4 && xhr.status == 200) {
                var data = eval("(" + xhr.responseText + ")");
                if (data.success === "true") {
                    closeAjaxBox();
                    self.loadPageHotsiteInterface();
                } else {
                    self.hotsiteConfigError(data.error);
                    return;
                }

            }
        };
        xhr.send(form);

    };

    this.renderPreview = function(render) {
        $("#preview-hotsite").html(render);
    };

    this.loadSideMenu = function(sidemenu) {
        var self = this;
        $(".hotsite-admnistrative-sidemenu .item").die("click");
        $("#leftbar-menu-hotsite").html(sidemenu);
        if ($(".hotsite-administrative-sidemenu .item[action=add-block]").length) {
            $(".hotsite-administrative-sidemenu .item[action=add-block]").die().live("click", function() {
                self.hotsiteCreateBlock(0, 0);
            });
        }
        if ($(".hotsite-administrative-sidemenu .item[action=add-content]").length) {
            $(".hotsite-administrative-sidemenu .item[action=add-content]").die().live("click", function() {
                self.hotsiteCreateContentForm();
            });

        }
    };


    this.hotsiteCreateContentForm = function() {
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_contents_create_types",
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    var sidemenu = $("#leftbar-menu-hotsite").html();
                    var html = "<div class='header'>Adicionar Conteúdo</div>";
                    html += "<div class='info'>Arraste o tipo de conteúdo que você deseja criar para o bloco de destino.</div>";
                    html += "<div id='hotsite-content-add-wrap'>";
                    $.each(data.content_types, function(index, content) {
                        html += "<div class='hotsite-content-add-select' content='" + content.id + "'>";
                        html += "<span class='fa " + content.icon + "'></span>";
                        html += content.label;
                        html += "</div>";
                    });
                    html += "</div>"
                    $("#leftbar-menu-hotsite").html(html);
                    var drag_content = dragula([document.getElementById("hotsite-content-add-wrap"), document.getElementsByClassName("block")], {
                        isContainer: function(el) {
                            return el.classList.contains('block');
                        }
                    });
                    drag_content.on('drop', function(el, target, source, sibling)
                    {
                        var split = target.id.split("-");
                        var id = parseInt(split[2]);
                        if (split[0] != "hotsite" || split[1] != "block" || isNaN(id)) {
                            drag_content.cancel(true);
                        }
                    });


                }
            }
        });

    };

    this.hotsiteCreateBlock = function(step, width) {
        var self = this;
        switch (step) {
            case 0:
                var sidemenu = $("#leftbar-menu-hotsite").html();
                var html = "<div class='header'>Adicionar Bloco</div>";
                html += "<div class='info'>Selecione a largura do bloco através do formulário abaixo.";
                html += "<select id='block-add-width-select'>";
                html += "<option value='100'>1 Coluna (100%)</option>";
                html += "<option value='50'>2 Colunas (50%)</option>";
                html += "<option value='33.3'>3 Colunas (30%)</option>";
                html += "<option value='25'>4 Colunas (25%)</option>";
                html += "<option value='20'>5 Colunas (20%)</option>";
                html += "<option value='12.5'>8 Colunas (12,5%)</option>";
                html += "</select>";
                html += "<input type='button' id='block-add-submit' value='Criar Bloco'>";
                html += "<input type='button' id='block-add-cancel' value='Cancelar'>";
                $("#leftbar-menu-hotsite").html(html);
                $("#block-add-cancel").die().live("click", function() {
                    $("#leftbar-menu-hotsite").html(sidemenu);
                });
                $("#block-add-submit").die().live("click", function() {
                    var width = parseInt($("#block-add-width-select").val());
                    self.hotsiteCreateBlock(1, width);
                });
                break;
            case 1:
                if (width <= 0 || isNaN(width)) {
                    $("#block-add-cancel").append("<div class='error'>Largura do bloco inválida.</div>");
                }
                $.ajax({
                    url: self.root + "/interface/ajax",
                    data: {
                        mode: "create_block",
                        width: width
                    },
                    success: function(data) {
                        data = eval("( " + data + " )");
                        if (data.success === "true") {
                            self.loadPageHotsiteInterface(self.loaded_page);
                        }
                    }
                });
                break;
        }
    };

    this.loadHotsiteConfigInterface = function() {
        var self = this;
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "load_hotsite_config_interface"
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    loadAjaxBox(data.html);
                    var pickers = new Array();
                    var infos = data.hotsite_config;
                    $("#hotsite-config-form .color-value").each(function(index, element) {
                        var field = $(this).attr("var");
                        pickers[field] = new jscolor(element);
                        if (infos[field] !== null) {
                            pickers[field].fromString(infos[field]);
                        } else {
                            pickers[field].fromString("ffffff");
                        }
                    });

                    if (infos.background_repeat === "true") {
                        $("#hotsite-config-form .item[ref=background-image] input[name=background-image-repeat]").attr("checked", true);
                    }

                    if (infos.background_image !== "" && infos.background_image !== null) {
                        var img = "<img src='/dyon/hotsite/background_image/" + infos.background_image + "' width='350'";
                        if (infos.background_repeat === "true") {
                            img += " repeat";
                        }
                        img += "/>";
                        $("#hotsite-config-form .item[ref=background-image] .image-value").html(img);
                        $("#hotsite-config-form .item[ref=background-image] .image-value").after("<input type='checkbox' name='background-image-remove'/> Remover Imagem");
                    } else {
                        var img = "Sem Imagem";
                        $("#hotsite-config-form .item[ref=background-image] .image-value").html(img);
                    }

                    $("#hotsite-config-form-submit").die().live("click", function() {
                        self.saveHotsiteConfig(infos);

                    });
                }
            }
        });
    };

    this.saveHotsiteConfig = function(infos) {
        var self = this;
        var color_pattern = /^[0-9A-F]{6}$/;
        var new_infos = new Object();
        self.hotsiteConfigError("clear");
        new_infos.text_color = $("#hotsite-config-form .item[ref=text-color] .value").html();
        new_infos.title_color = $("#hotsite-config-form .item[ref=title-color] .value").html();
        new_infos.background_color = $("#hotsite-config-form .item[ref=background-color] .value").html();
        new_infos.background_image_remove = $("#hotsite-config-form .item[ref=background-image] input[name=background-image-remove]").is(":checked");
        new_infos.background_repeat = $("#hotsite-config-form .item[ref=background-image] input[name=background-image-repeat]").is(":checked");
        if (!color_pattern.test(new_infos.text_color)) {
            self.hotsiteConfigError("A cor do texto está em formato inválido.");
            return;
        }
        if (!color_pattern.test(new_infos.title_color)) {
            self.hotsiteConfigError("A cor do título está em formato inválido.");
            return;
        }
        if (!color_pattern.test(new_infos.background_color)) {
            self.hotsiteConfigError("A cor do fundo está em formato inválido.");
            return;
        }

        if ($("#hotsite-background-image-file").val().length > 0) {
            new_infos.background_image = new Object();
            new_infos.background_image.file = document.getElementById("hotsite-background-image-file").files[0];
            new_infos.background_image.filename = $("#hotsite-background-image-file").val();
            new_infos.background_image.extension = new_infos.background_image.filename.substr(new_infos.background_image.filename.length - 3, 3).toLowerCase();
            if (new_infos.background_image.extension !== "jpg" && new_infos.background_image.extension !== "png" && new_infos.background_image.extension !== "gif") {
                self.hotsiteConfigError("A imagem que você enviou está em formato inválido.");
                return;
            }
        }

        var form = new FormData();
        var xhr = new XMLHttpRequest();

        form.append("mode", "submit_hotsite_config");
        if (new_infos.text_color !== infos.text_color) {
            form.append("text_color", new_infos.text_color);
        }
        if (new_infos.title_color !== infos.title_color) {
            form.append("title_color", new_infos.title_color);
        }
        if (new_infos.background_color !== infos.background_color) {
            form.append("background_color", new_infos.background_color);
        }
        if (new_infos.background_image) {
            form.append("background_image", new_infos.background_image.file);
            form.append("background_image_filename", new_infos.background_image.filename);
        }
        if (new_infos.background_repeat !== infos.background_repeat) {
            form.append("background_repeat", new_infos.background_repeat);
        }
        if (new_infos.background_image_remove) {
            form.append("background_image_remove", new_infos.background_image_remove);
        }
        xhr.open('POST', self.root + "/interface/ajax", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 2) {
            }
            if (xhr.readyState === 4 && xhr.status == 200) {
                var data = eval("(" + xhr.responseText + ")");
                if (data.success === "true") {
                    closeAjaxBox();
                    self.loadPageHotsiteInterface();
                } else {
                    self.hotsiteConfigError(data.error);
                    return;
                }

            }
        };
        xhr.send(form);
    };

    this.hotsiteConfigError = function(message) {
        $("#hotsite-config-form .error-log").html(message);
    };

    this.loadContentEditInterface = function(id) {
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "load_content_edit_interface",
                id: id
            },
            success: function(data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#content-edit-wrap").html(data.content.form);
                    $("#content-edit-wrap").css("display","block").animate({height:'350px'},300);
                }
            }
        });
    };
};