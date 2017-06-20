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

eventCheckoutInterface = function () {
    this.root = $("#dir-root").val();

    this.init = function () {
        var self = this;
        self.event_id = parseInt($("#get-ticket").attr("event"));
        if (isNaN(self.event_id)) {
            return;
        }

        self.bindCheckoutButton();
    };

    this.bindCheckoutButton = function () {
        var self = this;
        $(document).on("click", "#get-ticket", function () {
            self.loadLotSelectionForm();
        });
    };

    this.loadLotSelectionForm = function () {
        var self = this;
        $.ajax({
            url: self.root + "/e/ajax",
            data: {
                event_id: self.event_id,
                mode: "load_lot_selection_form"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    self.loadCheckoutBox(data.html);
                } else {

                }
            }
        });
    };

    this.loadCheckoutBox = function (html) {
        loadAjaxBox(html);
        $("#ajax-box").addClass("checkout-box");
        $("#ajax-box-background").addClass("checkout-box-background");
        $(document).off("click", "#ajax-box-background");

    };
};

publicEventInterface = function () {
    this.root = $("#dir-root").val();

    this.init = function () {
        var self = this;
        self.bindHeader();
        self.loadCheckoutInterface();
    };

    this.loadCheckoutInterface = function () {
        var checkout_interface = new eventCheckoutInterface();
        checkout_interface.init();
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