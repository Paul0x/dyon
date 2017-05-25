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

preferencesInterface = function () {

    this.root = $("#dir-root").val();
    this.init = function () {
        var self = this;
        self.setupButtons();
        self.checkTab();
    };

    this.checkTab = function () {
        var tab = window.location.hash.substr(1);
        if (tab) {
            if (tab === "instance" || tab === "privacy" || tab === "payment" || tab === "personal") {
                $("#user-preference-wrap .preference-tabs .item").removeClass("selected");
                $("#user-preference-wrap .preference-form .tab").css("display", "none");
                $("#user-preference-wrap .preference-tabs .item[tab=" + tab + "]").addClass("selected");
                $("#user-preference-wrap .preference-form .tab[tab=" + tab + "]").css("display", "block");
            }
        }
    };

    this.setupButtons = function () {
        var self = this;
        self.bindSidebarTabs();
        self.bindSubmitButtons();
        self.imageUploadSetup();
    };

    this.bindSubmitButtons = function () {
        var self = this;
        $("#personal-form-submit").bind("click", function () {
            self.updatePersonalSubmit();
        });

        $("#instance-form-add").bind("click", function () {
            self.createInstanceForm();
        });

        $(document).on("click", "#instance-info-form .instance-select-control", function () {
            self.updateSelectedInstance(this);
        });

    };

    this.bindSidebarTabs = function () {
        $("#user-preference-wrap .preference-tabs .item").bind("click", function () {
            var tab = $(this).attr("tab");
            $("#user-preference-wrap .preference-tabs .item").removeClass("selected");
            $(this).addClass("selected");
            $("#user-preference-wrap .preference-form .tab").css("display", "none");
            $("#user-preference-wrap .preference-form .tab[tab=" + tab + "]").css("display", "block");
        });
    };

    this.imageUploadSetup = function () {
        var self = this;
        $(document).on("click", "#user-preference-wrap #personal-info-form .image .edit", function () {
            $("#image-upload-file").trigger("click");
            $("#image-upload-file").on("click", function () {
                $("#image-upload-file").val("");

            });
        });

        $("#image-upload-file").change(function () {
            self.uploadProfileImage(this.files[0]);
        });
    };

    this.updateSelectedInstance = function (handler) {
        var self = this;
        var instance_id = $(handler).attr("instance");
        $.ajax({
            url: self.root + "/usuario/ajax",
            data: {
                mode: "update_selected_instance",
                instance_id: instance_id
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#instance-info-form .instance-table .instance-selected-control").addClass("instance-select-control").addClass("control").removeClass("instance-selected-control").html("Selecionar Equipe");
                    $("#instance-info-form .instance-table .instance-select-control[instance=" + data.instance + "]").addClass("instance-selected-control").removeClass("control").removeClass("instance-select-control").html("Equipe Selecionada");

                }
            }
        });
    };

    this.uploadProfileImage = function (file) {
        var self = this;
        var form = new FormData();
        var xhr = new XMLHttpRequest();
        form.append("image", file);
        form.append("mode", "upload_profile_image");
        xhr.open('POST', self.root + "/usuario/ajax", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 2) {
                $("#personal-info-form .image-file").css("display", "none");
                $("#personal-info-form .image").append("<i class='fa fa-spinner fa-spin' id='image-file-loading'></i>");
            }
            if (xhr.readyState == 4 && xhr.status == 200) {
                var data = eval("(" + xhr.responseText + ")");
                if (data.success == "true") {
                    $("#personal-info-form .image-file").css("display", "block");
                    $("#image-file-loading").remove();
                    $("#personal-info-form .image-file").attr("src", self.root + "/images/avatar/" + data.image);
                } else {
                    ajaxBoxMessage(data.error, "error");
                }
            }
        };
        xhr.send(form);
    };

    this.updatePersonalSubmit = function () {
        var self = this;
        var personal = new Object();
        personal.nome = $("#personal-info-form input[name=nome]").val();
        personal.email = $("#personal-info-form input[name=email]").val();
        personal.rg = $("#personal-info-form input[name=rg]").val();
        personal.nascimento_dia = $("#personal-info-form select[name=nascimento-day]").val();
        personal.nascimento_mes = $("#personal-info-form select[name=nascimento-month]").val();
        personal.nascimento_ano = $("#personal-info-form select[name=nascimento-year]").val();
        personal.sexo = $("#personal-info-form select[name=sexo]").val();
        personal.endereco = $("#personal-info-form input[name=endereco]").val();
        personal.cidade = $("#personal-info-form input[name=cidade]").val();
        personal.cep = $("#personal-info-form input[name=cep]").val();
        personal.estado = $("#personal-info-form select[name=estado]").val();

        if (personal.nome === "") {
            $("#personal-info-form input[name=nome]").addClass("error");
        }

        if (personal.email === "") {
            $("#personal-info-form input[name=email]").addClass("error");
        }

        $.ajax({
            url: self.root + "/usuario/ajax",
            data: {
                mode: "update_personal_info",
                infos: personal
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#personal-form-response").html("<div class='success'><i class='fa fa-check'></i> Informações Atualizadas com Sucesso</div>");
                } else {
                    $("#personal-form-response").html("<div class='error'><i class='fa fa-times'></i> " + data.error + "</div>");

                }
            }
        });
    };

    this.createInstanceForm = function () {
        var self = this;
        $.ajax({
            url: self.root + "/usuario/ajax",
            data: {
                mode: "load_instance_add_form"
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    $("#user-preference-wrap").html(data.html);
                }
            }
        });

    };

};

instanceAddInterface = function () {
    var self = this;
    this.root = $("#dir-root").val();
    this.init = function () {
        $(document).on("click", "#add-instance-wrap .instance-submit-plan", function () {
            self.selectPlan(this);
        });

        $(document).on("click", "#add-instance-wrap #instance-submit-name", function () {
            self.addInstance();
        });
    };

    this.selectPlan = function (plan) {
        var self = this;
        self.instance = new Object();
        self.instance.plan = $(plan).attr("plan");
        $("#add-instance-wrap .label").html("<span>2º - Nome</span> Escolha um nome para a sua equipe organizadora.");
        $("#add-instance-wrap .step").html("<input type='text' id='add-instance-name' placeholder='Selecione o nome da instância' />\n\
          <input type='button' id='instance-submit-name' value='Criar Equipe' /> ");
    };

    this.addInstance = function () {
        var self = this;
        if (isNaN(self.instance.plan)) {
            return;
        }

        self.instance.name = $("#add-instance-name").val();
        if (self.instance.name === "") {
            return; // TODO: MOSTRAR ERRO
        }
        $.ajax({
            url: self.root + "/usuario/ajax",
            data: {
                mode: "add_instance",
                plan: self.instance.plan,
                name: self.instance.name
            },
            success: function (data) {
                data = eval("( " + data + " )");
                if (data.success === "true") {
                    window.location = self.root + "/manager";
                }
            }
        });

    };

};