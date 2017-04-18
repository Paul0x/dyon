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
        
        if(personal.nome === "") {
            $("#personal-info-form input[name=nome]").addClass("error");
        }
        
        if(personal.email === "") {
            $("#personal-info-form input[name=email]").addClass("error");
        }
        
        $.ajax({
                url: self.root + "/usuario/ajax",
                data: {
                    mode: "update_personal_info",
                    infos: personal
                },
                success: function(data) {
                    data = eval("( " + data + " )");
                    if (data.success === "true") {
                    }
                }
            });

    };

};