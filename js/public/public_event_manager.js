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
    
    this.init = function(event_id) {
        var self = this;
        if(isNaN(event_id)) {
            return;
        }
        self.event_id = event_id;
        self.loadControls();
        self.loadBottomBar();
        self.bindBottomBarControls();
    };
    
    this.loadControls = function() {
        $("#public-event-wrap .content .main").append("<div class='editable-content' id='public-event-manager-edit-description'><i class='fa fa-pencil'></i> Editar Descrição</div>")
    };
    
    this.bindBottomBarControls = function() {
        var self = this;
        $(document).on("click", "#public-event-manager-wrap .buttons .button", function() {
            switch(this.id) {
                case "public-event-manager-edit-appearance":
                    self.loadAppearanceEditForm();
                    break;
                case "public-event-manager-edit-settings":
                    self.loadSettingsEditForm();
            }            
        });        
    };
    
    this.loadAppearanceEditForm = function() {
        var self = this;
        self.loadManagerForm("appearance");
    };
    
    this.loadSettingsEditForm = function() {
        var self = this;
        self.loadManagerForm("settings");
    };
    
    this.loadManagerForm = function(form) {
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
                }
            }
        });
    };
    
    this.loadBottomBar = function() {
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