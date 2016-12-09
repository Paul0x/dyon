//
//  Arquivo de funções.
//
//

function htmlEncode(string)
{
    /**
     *  Converte caracteres especiais para seus respectivos códigos html.
     *  OBS: Isso é utilizado para requisições AJAX, já que o javascript não reconhece os caracteres por padrão.
     *  
     */

    string = string.replace(/&/g, "&amp;");
    string = string.replace(/'/g, "&#039;");
    string = string.replace(/"/g, "&quot;");
    return string;

};


function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;")
        .replace(/\//g, "&#x2F;")
};

function sideMenuHeightFix() 
{
    var body = document.body;
    var html = document.documentElement;
    var height = Math.max( body.scrollHeight, body.offsetHeight, 
                       html.clientHeight, html.scrollHeight, html.offsetHeight, window.innerHeight );
    $(".sidebar-menu, #dashboard-sidebar").css("height",$(document).height()+50);
};

function formatStr()
{
    this.formatReal = function (number, places, symbol, thousand, decimal) {
        number = number || 0;
        places = !isNaN(places = Math.abs(places)) ? places : 2;
        symbol = "R$";
        thousand = thousand || ",";
        decimal = decimal || ".";
        var negative = number < 0 ? "-" : "",
                i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
                j = (j = i.length) > 3 ? j % 3 : 0;
        return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
    };
}

function setAutocompleteForms(field_id, items, field_recieper)
{
    $(".autocomplete").remove();

    if ($("#" + field_id + "-autocomplete").length === 0) {
        $("#" + field_id).after("<div id=\"" + field_id + "-autocomplete\" class=\"autocomplete\"></div>");
        var position = $("#" + field_id).offset();
        var height = $("#" + field_id).height();
        var width = $("#" + field_id).width();

        $("#" + field_id + "-autocomplete").css("top", position.top + height + 10);
        $("#" + field_id + "-autocomplete").css("left", position.left);
        $("#" + field_id + "-autocomplete").css("min-width", width + 10);

        $("#" + field_id).bind("focusout", function () {
            setTimeout(function () {
                $("#" + field_id + "-autocomplete").remove();
                $("#" + field_id).unbind("focusout");
                $("#" + field_id + "-autocomplete .autocomplete-item").unbind("click");
            }, 500);
        });

        $(".autocomplete-item").live("click", function () {
            $("#" + field_recieper).val(this.getAttribute("value"));
            $("#" + field_id).val(this.innerHTML);
        });
    }

    var html = "";
    $.each(items, function (index, item) {
        html += "<div class='autocomplete-item' value='" + item[1] + "'>" + item[0] + "</div>";
    });

    $("#" + field_id + "-autocomplete").html(html);
}

function loadAjaxBox(html) {
    if ($("#ajax-box").length === 0) {
        $("body").prepend("<div id='ajax-box'></div><div id='ajax-box-background'></div>");
        $(".ajax-close-box").live("click", function () {
            closeAjaxBox();
        });
    }
    $("html, body").scrollTop(0);

    $("#ajax-box").html(html);
    $("#ajax-box, #ajax-box-background").fadeIn();
       
    $("#ajax-box-background").die().live("click", function() { closeAjaxBox(); });
}

function loadBigAjaxBox(html) {
    if ($("#ajax-box").length === 0) {
        $("body").prepend("<div id='ajax-box' class='big-ajax-box'></div><div id='ajax-box-background'></div>");
        $(".ajax-close-box").live("click", function () {
            closeAjaxBox();
        });
    }

    $("#ajax-box").html(html);
    $("#ajax-box, #ajax-box-background").stop(true, true).fadeIn();
}

function closeAjaxBox() {
    $("#ajax-box").fadeOut(500, function () {
        $("#ajax-box, #ajax-box-background").remove();
        $(".ajax-close-box").die("click");
    });

}

function ajaxBoxMessage(message, type) {
    var css_class;
    if (type == "error") {
        css_class = "ajax-box-error";
    } else if (type == "success") {
        css_class = "ajax-box-success";
    } else {
        css_class = "ajax-box-dialog";
    }


    var error_timeout;
    if ($("." + css_class).length == 0) {
        $("#ajax-box").append("<div class='" + css_class + "'>" + message + "</div>");
        error_timeout = setTimeout(function () {
            $(".ajax-box-error").remove();
        }, 8000);
    } else {
        $("." + css_class).html(message);
        window.clearTimeout(error_timeout);
        error_timeout = setTimeout(function () {
            $("." + css_class).remove();
        }, 8000);
    }

}

function controlsInterface() {
    this.root = $("#dir-root").val();
    this.init = function () {
        var self = this;
        $("#button-menu").bind("click", function () {
            self.menuToggle();
        });
        
        $("#main-navigation-menu-wrap .instance-changer").live("click", function() {
            self.changeInstance(this);            
        });
    };

    this.changeInstance = function (instance_button) {
        var id = parseInt($(instance_button).attr("instance"));
        if(isNaN(id)) {
            return;
        }
        
        $.ajax({
                url: self.root + "/usuario/ajax",
                data: {
                    mode: "change_user_instance",
                    instance_id: id
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        location.reload();
                    }
                }
            });
        
        
    };
    
    this.menuToggle = function () {
        var self = this;
        if ($("#main-navigation-menu-wrap").length === 0) {

            $.ajax({
                url: self.root + "/usuario/ajax",
                data: {
                    mode: "load_menu"
                },
                success: function (data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                        $("#button-menu").addClass("selected");
                        $("#button-menu").after(data.html);
                    }
                }
            });
        } else {
            if ($("#main-navigation-menu-wrap").is(":hidden")) {
                $("#main-navigation-menu-wrap").show();
                $("#button-menu").addClass("selected");

            } else {
                $("#main-navigation-menu-wrap").hide();
                $("#button-menu").removeClass("selected");

            }

        }
    };
}