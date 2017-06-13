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
 *  File: public_event_manager.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

publicEventManagerInterface = function () {
    this.root = $("#dir-root").val();

    this.init = function (event_id) {
        var self = this;
        if (isNaN(event_id)) {
            return;
        }
        self.event_id = event_id;
        self.loadControls();
        self.loadBottomBar();
        self.bindBottomBarControls();
    };

    this.loadControls = function () {
        $("#public-event-wrap .content .main").append("<div class='editable-content' id='public-event-manager-edit-description'><i class='fa fa-pencil'></i> Editar Descrição</div>")
    };

    this.bindBottomBarControls = function () {
        var self = this;
        $(document).on("click", "#public-event-manager-wrap .buttons .button", function () {
            switch (this.id) {
                case "public-event-manager-edit-appearance":
                    self.loadAppearanceEditForm();
                    break;
                case "public-event-manager-edit-settings":
                    self.loadSettingsEditForm();
            }
        });
    };

    this.loadAppearanceEditForm = function () {
        var self = this;
        self.loadManagerForm("appearance", function () {
            var pickers = new Array();
            $(".public-manager-form-wrap .info-wrap .item .color").each(function (index, element) {
                var field = $(this).attr("field");
                pickers[field] = new jscolor(element);
                var color = $(".public-manager-form-wrap .info-wrap .item input[field=" + field + "]").val();
                if (color === "") {
                    pickers[field].fromString("ffffff");
                } else {
                    pickers[field].fromString(color);
                }

            });


            $(document).off("click", "#public-event-manager-appearance-wrap #submit-button").on("click", "#public-event-manager-appearance-wrap #submit-button", function () {
                self.submitAppearanceEditForm();
            });
        });

    };

    this.loadSettingsEditForm = function () {
        var self = this;
        self.loadManagerForm("settings");
        $(document).off("click", "#public-event-manager-settings-wrap .button-item .button").on("click", "#public-event-manager-settings-wrap .button-item .button", function () {
            var field = $(this).attr("field");
            var value = parseInt($("#public-event-manager-settings-wrap input[field=" + field + "]").val());
            if (value === 0) {
                $("#public-event-manager-settings-wrap .button-item .button[field=" + field + "]").html('<i class="fa fa-toggle-on"></i>');
                $("#public-event-manager-settings-wrap input[field=" + field + "]").val(1);
            } else {
                $("#public-event-manager-settings-wrap .button-item .button[field=" + field + "]").html('<i class="fa fa-toggle-off"></i>');
                $("#public-event-manager-settings-wrap input[field=" + field + "]").val(0);
            }
        });
        $(document).off("click", "#public-event-manager-settings-wrap #submit-button").on("click", "#public-event-manager-settings-wrap #submit-button", function () {
            self.submitSettingsEditForm();
        });
    };

    this.submitSettingsEditForm = function () {
        var self = this;
        var settings = new Object();

        var fields_input = new Array("show-schedule", "show-gallery", "show-contacts", "show-likes", "show-sold", "published", "contact-phone", "contact-email", "contact-address");
        var fields_select = new Array("button-name");

        $.each(fields_input, function (idx, field) {
            var underline_field = field.replace("-","_");
            settings[underline_field] = $("#public-event-manager-settings-wrap input[field=" + field + "]").val();
        });

        $.each(fields_select, function (idx, field) {
            var underline_field = field.replace("-","_");
            settings[underline_field] = $("#public-event-manager-settings-wrap select[field=" + field + "]").val();
        });

        settings["mode"] = "submit_edit_settings";
        settings["event_id"] = self.event_id;

        $.ajax({
            url: self.root + "/e/ajax",
            data: settings,
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    
                }
            }
        });
    };


    this.submitAppearanceEditForm = function () {
        var self = this;
        var appearance = new FormData();

        var fields_input = new Array("teaser");
        var fields_color = new Array("background_color", "date_color", "title_color");
        var fields_image = new Array("image_banner");
        $.each(fields_input, function (idx, field) {
            appearance[field] = $("#public-event-manager-appearance-wrap input[field=" + field + "]").val();
        });

        $.each(fields_color, function (idx, field) {
            appearance[field] = $("#public-event-manager-appearance-wrap .color-item .color[field=" + field + "]").html();
        });

        $.each(fields_image, function (idx, field) {
            if ($("#public-event-manager-appearance-wrap input[field=" + field + "]").val().length > 0) {
                appearance[field] = new Object();
                appearance[field].file = document.getElementById("public-event-manager-appearance-file-" + field).files[0];
                alert(appearance[field].file);
                appearance[field].filename = $("#public-event-manager-appearance-wrap input[field=" + field + "]").val();
                appearance[field].extension = appearance[field].filename.substr(appearance[field].filename.length - 3, 3).toLowerCase();
                if (appearance[field].extension !== "jpg" && appearance[field].extension !== "png" && appearance[field].extension !== "gif") {
                    return;
                }
            }
            alert("ok");
        });

        appearance["mode"] = "submit_edit_appearance";
        appearance["event_id"] = self.event_id;
        
        var xhr = new XMLHttpRequest();
        
        xhr.open('POST', self.root + "/e/ajax", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 2) {
            }
            if (xhr.readyState === 4 && xhr.status == 200) {
                var data = eval("(" + xhr.responseText + ")");
                if (data.success === "true") {
                    closeAjaxBox();
                    self.loadPageHotsiteInterface();
                } 
            }
        };
        xhr.send(appearance);
    };

    this.loadManagerForm = function (form, callback) {
        var self = this;
        $.ajax({
            url: self.root + "/e/ajax",
            data: {
                mode: "load_manager_form",
                form: form,
                event_id: self.event_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    loadAjaxBox(data.html);
                    if (callback) {
                        callback();
                    }
                }
            }
        });
    };

    this.loadBottomBar = function () {
        var self = this;
        $.ajax({
            url: self.root + "/e/ajax",
            data: {
                mode: "load_manager_bottom_bar",
                event_id: self.event_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#public-event-wrap").append(data.html);
                }
            }
        });
    };
};