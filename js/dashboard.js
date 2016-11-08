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

dashboardInterface = function () {
    this.root = $("#dir-root").val();
    
    this.load = function() {
        var self = this;
        $.ajax({
            url: self.root + "/eventos/ajax",
            data: {
                mode: "get_dashboard_event"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#dashboard-sidebar").html(data.html)
                }
            }
        });
        
    };

};