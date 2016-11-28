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
 *  File: status.js
 *  Type: Javascript Library
 *  =====================================================================
 */

(function ($) {
    $.clock = {version: "1.0.0", locale: {}};
    t = [];
    $.fn.clock = function (d) {
        return this.each(function () {
            var obj = this;
            console.log("Iniciado Aplicativo de Relógio");
            var date = new Date();
            var clock_timer = new Object();
            var current_timestamp = Math.floor(parseInt(date.getTime()) / 1000);
            var duration = current_timestamp - d.timestamp;

            clock_timer.seconds = duration;
            clock_timer.hours = Math.floor(clock_timer.seconds / 3600);
            clock_timer.seconds -= (clock_timer.hours * 3600);
            clock_timer.minutes = Math.floor((clock_timer.seconds / 60) % 60);
            clock_timer.seconds -= (clock_timer.minutes * 60);
            clock_timer.seconds = clock_timer.seconds % 60;

            $.fn.runtime = setInterval(function () {
                clock_timer.seconds++;
                if (clock_timer.seconds >= 60) {
                    clock_timer.seconds = 0;
                    clock_timer.minutes++;
                }
                if (clock_timer.minutes >= 60) {
                    clock_timer.minutes = 0;
                    clock_timer.hours++;
                }
                clock_timer.viewhours = (clock_timer.hours < 10 ? "0" + clock_timer.hours : clock_timer.hours);
                clock_timer.viewminutes = (clock_timer.minutes < 10 ? "0" + clock_timer.minutes : clock_timer.minutes);
                clock_timer.viewseconds = (clock_timer.seconds < 10 ? "0" + clock_timer.seconds : clock_timer.seconds);
                var show = clock_timer.viewhours + ":" + clock_timer.viewminutes + ":" + clock_timer.viewseconds;
                $(obj).html(show);
            }, 1000);

            f($(this), d);
        });
    };

    $.fn.stop = function () {
        window.clearInterval($.fn.runtime);

    };
    return this;
})(jQuery);

statusController = function () {
    this.root = $("#dir-root").val();
    this.statusobj;

    this.loadStatusSystem = function (status_info) {
        var self = this;
        self.statusobj = new Object();
        self.bindControls();
        self.setCurrentStatus(parseInt(status_info.current_status));
        self.setCurrentUser(status_info.current_user);
        self.setTimer(parseInt(status_info.last_update));
    };

    this.bindControls = function () {
        var self = this;
        $("#statussystem-wrap .control").die().live("click", function () {
            var action = $(this).attr("act");
            switch (action) {
                case 'change':
                    self.loadChangeStatusForm();
                    break;
                case 'history':
                    self.loadHistory();
                    break;
            }

        });
    };

    this.loadChangeStatusForm = function () {
        var html = "<select id='thread-ss-change-status'>";
        html+= "<option value=''>SELECIONE O STATUS</option>";
        html+= "<option value='0'>EM ESPERA</option>";
        html+= "<option value='1'>EM DESENVOLVIMENTO</option>";
        html+= "<option value='2'>EM TRABALHO</option>";
        html+= "<option value='3'>COMPLETADO</option>";
        html+= "</select>";
        $("#statussystem-wrap .current-status").css("display","none");        
        $("#statussystem-wrap .form").html(html);
    };

    this.stopTimer = function () {
        $("#statussystem-wrap .current-status .timer").stop();
    };

    this.setCurrentUser = function (user) {
        var html = "Atribuido por: <strong>" + user + "</strong>";
        $("#statussystem-wrap .current-status .user").html(html);
    };

    this.setCurrentStatus = function (status) {
        var self = this;

        if (isNaN(status) || status < 0 || status > 3) {
            console.log("O status precisa ser um numeral entre 0 e 3");
            return;
        }

        self.statusobj.current_status = status;
        var html;
        switch (status) {
            case 0:
                html = "<span class='idle'>EM ESPERA</span>";
                break;
            case 1:
                html = "<span class='development'>EM DESENVOLVIMENTO</span>";
                break;
            case 2:
                html = "<span class='working'>TRABALHANDO</span>";
                break;
            case 3:
                html = "<span class='completed'>CONCLUÍDO</span>";
                break;
        }

        $("#statussystem-wrap .current-status .status").html(html);

    };

    this.setTimer = function (timestamp) {
        $("#statussystem-wrap .current-status .timer").clock({"timestamp": timestamp});

    };

};