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
 *  File: event.js
 *  Type: Javascript Controller
 *  =====================================================================
 */

eventInterface = function () {

    this.event_id;

    this.getEventId = function () {
        this.event_id = $("#event-id").val();
    };

    this.loadBindsLoad = function () {
        var event_id = this.event_id;
        var interface = this;
        $("#event-info-add-lote").bind("click", function () {
                $.ajax({
                    url: root + "/eventos/" + event_id + "/lote/add",
                    success: function (data) {
                        data = eval("( " + data + ")");
                        loadBigAjaxBox(data.html);
                        $("#event-add-lote-btn").bind("click", function () {
                            interface.addLote();
                        });
                    }
                });
        });

        $(".lote-status").bind("click", function () {
            interface.editLoteStatus(this);
        });
    };

    this.loadBindsMain = function () {
        var interface = this;
        $(".event-archive").bind("click", function () {
            interface.archiveEvent(this.id.split("-")[2]);
        });
    };


    this.archiveEvent = function (evento_id) {
        $.ajax({
            url: root + "/eventos/archive",
            data: {
                id: evento_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    switch (data.status) {
                        case 1:
                            $("#event-list-item-" + evento_id + " .event-archive").html("Desarquivar");
                            $("#event-list-item-" + evento_id + " .nome").after('<div class="event-archived">Evento Arquivado</div>');
                            break;
                        case 2:
                            $("#event-list-item-" + evento_id + " .event-archive").html("Arquivar");
                            $("#event-list-item-" + evento_id + " .event-archived").remove();
                            break;
                    }

                }

            }
        });
    };

    this.editLoteStatus = function (lote) {
        var event_id = this.event_id;
        var id = lote.id.split("-")[2];
        var id_complete = lote.id;
        $.ajax({
            url: root + "/eventos/" + event_id + "/lote/status",
            data: {
                lote_id: id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    switch (data.status) {
                        case 1:
                            $("#" + id_complete).removeClass("lote-status-2").addClass("lote-status-1").html("Fechado");
                            break;
                        case 2:
                            $("#" + id_complete).removeClass("lote-status-1").addClass("lote-status-2").html("Aberto");
                            break;
                    }

                }
            }
        });
    };

    this.addLote = function () {
        var nome, valor, max_venda, genero, tipo, parent;
        var event_id = this.event_id;
        var interface = this;

        nome = $(".event-add-lote-input[name=nome]").val();
        tipo = $(".event-add-lote-select[name=tipo]").val();
        valor = $(".event-add-lote-input-small[name=valor]").val();
        valor = valor.replace(".", "").replace(",", ".");
        max_venda = $(".event-add-lote-input-small[name=max_venda]").val();
        genero = $(".event-add-lote-select[name=genero]").val();
        parent = $(".event-add-lote-select[name=parent]").val();
        $.ajax({
            url: root + "/eventos/" + event_id + "/lote/add",
            data: {
                nome: nome,
                valor: valor,
                max_venda: max_venda,
                genero: genero,
                tipo: tipo,
                parent: parent,
                submit: true
            },
            success: function (data) {
                data = eval("( " + data + ")");        

            }
        });
    };

};

eventGraphs = function () {


    this.root = $("#dir-root").val();

    this.overviewGraph = function () {
        var max_venda = parseInt($("#event-max-venda").val());
        var vendidos = parseInt($("#event-vendidos").val());
        if (!isNaN(max_venda) && max_venda > vendidos) {
            var restantes = max_venda - vendidos;
        }
        else {
            restantes = 0;
        }
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Item');
        data.addColumn('number', 'Valores');
        data.addRows([
            ['Vendidos', vendidos],
            ['Restantes', restantes]
        ]);

        // Set chart options
        var options = {
            'width': 250,
            'height': 250,
            'backgroundColor': 'none',
            legend: 'none',
            'pieSliceText': 'value',
            'colors': ['#666', '#bbb'],
            'chartArea': {'width': '90%', 'height': '90%'},
            'pieHole': .5
        };

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.PieChart(document.getElementById('overview-chart'));
        chart.draw(data, options);

    };


    this.lotesGraph = function () {
        var self = this;
        var event = $("#event-id").val();
        $.ajax({
            url: self.root + "/eventos/ajax",
            data: {
                mode: "get_event_lotes",
                event: event
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {

                    var lotes = new Array();
                    $.each(data.lotes, function (index, lote) {
                        var larray = new Array(lote.nome, lote.vendidos);
                        lotes.push(larray);
                    });
                    var datatable = new google.visualization.DataTable();
                    datatable.addColumn('string', 'Item');
                    datatable.addColumn('number', 'Valores');
                    datatable.addRows(lotes);

                    // Set chart options
                    var options = {
                        'width': 250,
                        'height': 250,
                        'backgroundColor': 'none',
                        legend: 'none',
                        'colors': ['#8ac', '#79b', "#469"],
                        'pieSliceText': 'label',
                        'chartArea': {'width': '90%', 'height': '90%'},
                    };

                    // Instantiate and draw our chart, passing in some options.
                    var chart = new google.visualization.PieChart(document.getElementById('overview-chart'));
                    chart.draw(datatable, options);
                }
            }
        });

    };

    this.selectOverviewGraph = function (option) {
        var self = this;
        switch (option) {
            case 0:
                self.overviewGraph();
                break;
            case 1:
                self.lotesGraph();
                break;

        }
    };

    this.bindGraphFunctions = function () {
        var self = this;
        $("#event-overview-select-graphs").bind("change", function () {
            self.selectOverviewGraph(parseInt(this.value));
        });
    };

};