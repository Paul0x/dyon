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
        $.ajax({
            url: self.root + "/interface/ajax",
            data: {
                mode: "load_hotsite_config_interface"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    loadBigAjaxBox(data.html);
                }
            }
        });
        
    };
};