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
                }
            }
        });
    };

    this.bindMenuController = function() {
        var self = this;
        $("#hotsite-administrative-topmenu .item[ref=config]").bind("click", function() {
            self.loadHotsiteConfigInterface();
        });
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

                    $("#hotsite-config-form-submit").die().live("click", function() {
                        self.saveHotsiteConfig(infos);

                    });
                }
            }
        });
    };

    this.saveHotsiteConfig = function() {
        var new_infos = new Object();
        new_infos.text_color = $("#hotsite-config-form .item[ref=text-color] .value").html();
        new_infos.title_color = $("#hotsite-config-form .item[ref=title-color] .value").html();
        new_infos.background_color = $("#hotsite-config-form .item[ref=background-color] .value").html();
        
        alert(new_infos.text_color+" - "+new_infos.background_color+" - "+new_infos.title_color);
    };
};