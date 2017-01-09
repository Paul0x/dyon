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

(function($) {
    $.clock = {version: "1.0.0", locale: {}};
    t = [];
    $.fn.clock = function(d) {
        return this.each(function() {
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

            $.fn.runtime = setInterval(function() {
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

        });
    };

    $.fn.stop = function() {
        window.clearInterval($.fn.runtime);

    };
    return this;
})(jQuery);

statusController = function() {
    this.root = $("#dir-root").val();
    this.updateStatusCallback;
    this.threadId;
    this.history;

    this.loadStatusSystem = function(status_info, thread_id, update_callback) {
        var self = this;
        self.updateStatusCallback = update_callback;
        self.history = status_info.history;
        self.threadId = thread_id;
        self.bindControls();
        self.setCurrentStatus(parseInt(status_info.current_status));
        self.setCurrentUser(status_info.current_user);
        self.setTimer(parseInt(status_info.last_update));
    };

    this.updateStatusSystem = function(thread) {
        var self = this;
        self.threadId = thread.id;
        self.setCurrentStatus(parseInt(thread.ss.current_status));
        self.setCurrentUser(thread.ss.current_user);
        self.setTimer(parseInt(thread.ss.last_update));
    };

    this.bindControls = function() {
        var self = this;
        $(document).off("click", "#statussystem-wrap .control").on("click", "#statussystem-wrap .control", function() {
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


        $(document).off("click", "#statussystem-wrap .form .back").on("click", "#statussystem-wrap .form .back", function() {
            self.revertStatusInterface();
        });
    };

    this.changeStatus = function() {
        var self = this;
        var status = parseInt($("#thread-ss-change-status").val());
        if (isNaN(status) || status < 0 || status > 3) {
            return;
        }

        self.updateStatusCallback(self.threadId, status);
    };

    this.revertStatusInterface = function() {
        $("#statussystem-wrap .current-status").css("display", "block");
        $("#statussystem-wrap .controls .current-control").css("display", "block");
        $("#statussystem-wrap .form").html("");

    };

    this.loadChangeStatusForm = function() {
        var self = this;
        var html = "<select id='thread-ss-change-status'>";
        html += "<option value=''>SELECIONE O STATUS</option>";
        html += "<option value='0'>EM ESPERA</option>";
        html += "<option value='1'>EM DESENVOLVIMENTO</option>";
        html += "<option value='2'>EM TRABALHO</option>";
        html += "<option value='3'>COMPLETADO</option>";
        html += "</select>";
        html += "<div id='thread-ss-assign-status' class='control'><i class='fa fa-hand-o-right'></i> Atribuir</div>";
        html += "<div class='control back'><i class='fa fa-times'></i> Voltar</div>";
        $("#statussystem-wrap .current-status").css("display", "none");
        $("#statussystem-wrap .controls .current-control").css("display", "none");
        $("#statussystem-wrap .form").html(html);
        $(document).off("click", "#thread-ss-assign-status").on("click", "#thread-ss-assign-status", function() {
            self.changeStatus();
        });
    };

    this.stopTimer = function() {
        $("#statussystem-wrap .current-status .timer").stop();
    };

    this.setCurrentUser = function(user) {
        var html = "Atribuido por: <strong>" + user + "</strong>";
        $("#statussystem-wrap .current-status .user").html(html);
    };

    this.setCurrentStatus = function(status) {
        var self = this;
        if (isNaN(status) || status < 0 || status > 3) {
            console.log("O status precisa ser um numeral entre 0 e 3");
            return;
        }

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

    this.setTimer = function(timestamp) {
        var self = this;
        self.stopTimer();
        $("#statussystem-wrap .current-status .timer").clock({"timestamp": timestamp});
    };
    
    this.updateHistory = function(history) {
        var self = this;
        self.history = history;
    };

    this.loadHistory = function() {
        var self = this;
        var history_converted = new Array();
        $.each(self.history, function(idx, status) {
            var time = new Object;
            time.label = self.translateTerm(idx);
            time.hourobj = self.timestampToHour(status.time_elapsed);
            time.elapsed = time.hourobj.viewhours + ":" + time.hourobj.viewminutes + ":" + time.hourobj.viewseconds;
            history_converted.push(time);
        });

        var html = "<div class='history-log'>";
        $.each(history_converted, function(idx, status) {
            html += "<div class='status-" + status.label + " status'>";
            html += "<div class='label'>" + status.label + "</div>";
            html += "<div class='elapsed'>" + status.elapsed + "</div>";
            html += "</div>";
        });
        html += "<div class='control back'><i class='fa fa-times'></i> Voltar</div>";
        $("#statussystem-wrap .current-status").css("display", "none");
        $("#statussystem-wrap .controls .current-control").css("display", "none");
        $("#statussystem-wrap .form").html(html);
        html += "</div>";

    };

    this.translateTerm = function(term) {
        switch (term) {
            case 'idle':
                return "EM ESPERA";
                break;
            case 'development':
                return "EM DESENVOLVIMENTO";
                break;
            case 'working':
                return "EM TRABALHO";
                break;
            case 'completed':
                return "COMPLETO";
                break;
        }
    };

    this.timestampToHour = function(timestamp) {
        var time = new Object();
        time.seconds = timestamp;
        time.hours = Math.floor(time.seconds / 3600);
        time.seconds -= (time.hours * 3600);
        time.minutes = Math.floor((time.seconds / 60) % 60);
        time.seconds -= (time.minutes * 60);
        time.seconds = time.seconds % 60;

        time.viewhours = (time.hours < 10 ? "0" + time.hours : time.hours);
        time.viewminutes = (time.minutes < 10 ? "0" + time.minutes : time.minutes);
        time.viewseconds = (time.seconds < 10 ? "0" + time.seconds : time.seconds);
        return time;
    };

};