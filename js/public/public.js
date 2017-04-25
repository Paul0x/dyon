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
 *  File: public.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

publicInterface = function () {
    this.root = $("#dir-root").val();
};

signupInterface = function () {
    this.root = $("#dir-root").val();

    this.init = function () {
        var self = this;
        self.bindSubmit();
    };

    this.bindSubmit = function () {
        $("#signup-submit").bind("click", function () {
            var error = 0;
            if ($("#signup-form input[name=nome]").val() === "") {
                $("#signup-form input[name=nome]").addClass("error");
                error++;
            }
            if ($("#signup-form input[name=email]").val() === "") {
                $("#signup-form input[name=email]").addClass("error");
                error++;
            }
            if ($("#signup-form input[name=senha]").val() === "") {
                $("#signup-form input[name=senha]").addClass("error");
                error++;
            }

            if (error > 0) {
                return false;
            } else {
                $("#signup-form input").removeClass("error");
                return true;
            }

        });
    };
};

publicEventInterface = function () {
    this.root = $("#dir-root").val();

    this.init = function () {
        var self = this;
        self.bindHeader();
    };


    this.bindHeader = function () {
        $("#main-header-wrap .title img").bind("click", function () {
            if ($("#main-header-wrap").hasClass("event-interface")) {
                $("#main-header-wrap").animate({width: "100%"}, function () {
                    $("#main-header-wrap").removeClass("event-interface");
                });
            } else {
                $("#main-header-wrap").animate({width: "90px"}, function () {
                    $("#main-header-wrap").removeAttr("style");
                    $("#main-header-wrap").addClass("event-interface");
                });
            }
            return false;
        });
    };

};