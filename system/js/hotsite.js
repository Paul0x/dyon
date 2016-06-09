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

hotsiteInterface = function () {

    this.root = $("#dir-root").val();
    this.init = function (id) {
        var self = this;
        self.loadHotsiteInterface();
    };

    this.loadHotsiteInterface = function () {
        var self = this;
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_hotsite_interface"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#topbar-menu-hotsite .menu-wrap").html(data.modules.topmenu);
                    self.bindMenuController();
                    self.loadPageHotsiteInterface();
                }
            }
        });
    };

    this.bindMenuController = function () {
        var self = this;
        $("#hotsite-administrative-topmenu .item[ref=config]").bind("click", function () {
            self.loadHotsiteConfigInterface();
        });
    };
    
    this.loadPageHotsiteInterface = function () {
        var self = this;
        
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "get_hotsite_page",
                page: 1
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    self.loadSideMenu(data.page.sidemenu);
                }
            }
        });
        
    };
    
    this.loadSideMenu = function(sidemenu) {
        $("#leftbar-menu-hotsite").html(sidemenu);
    };

    this.loadHotsiteConfigInterface = function () {
        var self = this;
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "load_hotsite_config_interface"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    loadAjaxBox(data.html);
                    var pickers = new Array();
                    var infos = data.hotsite_config;
                    $("#hotsite-config-form .color-value").each(function (index, element) {
                        var field = $(this).attr("var");
                        pickers[field] = new jscolor(element);
                        if (infos[field] !== null) {
                            pickers[field].fromString(infos[field]);
                        } else {
                            pickers[field].fromString("ffffff");
                        }
                    });
                    
                    if(infos.background_repeat === "true") {
                        $("#hotsite-config-form .item[ref=background-image] input[name=background-image-repeat]").attr("checked",true);
                    }

                    $("#hotsite-config-form-submit").die().live("click", function () {
                        self.saveHotsiteConfig(infos);

                    });
                }
            }
        });
    };

    this.saveHotsiteConfig = function (infos) {
        var self = this;
        var color_pattern = /^[0-9A-F]{6}$/;
        var new_infos = new Object();
        self.hotsiteConfigError("clear");
        new_infos.text_color = $("#hotsite-config-form .item[ref=text-color] .value").html();
        new_infos.title_color = $("#hotsite-config-form .item[ref=title-color] .value").html();
        new_infos.background_color = $("#hotsite-config-form .item[ref=background-color] .value").html();
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
        xhr.open('POST', self.root + "/interface/ajax", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 2) {
            }
            if (xhr.readyState == 4 && xhr.status == 200) {

            }
        };
        xhr.send(form);
    };

    this.hotsiteConfigError = function (message) {
        $("#hotsite-config-form .error-log").html(message);
    };
};