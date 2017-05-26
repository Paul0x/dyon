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
        self.loadBottomBar();
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