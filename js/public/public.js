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
 *  File: preferences.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

preferencesInterface = function() {

    this.root = $("#dir-root").val();
    this.init = function() {
        var self = this;
        self.setupButtons();
    };
    
    this.setupButtons = function() {
        var self = this;
        self.bindSidebarTabs();
    };
    
    this.bindSidebarTabs = function() {
        $("#user-preference-wrap .preference-tabs .item").bind("click", function() {
           var tab = $(this).attr("tab");
           $("#user-preference-wrap .preference-tabs .item").removeClass("selected");
           $(this).addClass("selected");
           $("#user-preference-wrap .preference-form .tab").css("display","none");
           $("#user-preference-wrap .preference-form .tab[tab="+tab+"]").css("display","block");
        });
    };

};