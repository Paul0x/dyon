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
        self.imageUploadSetup();
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
        form.append("image",file);
        form.append("mode", "upload_profile_image");
        xhr.open('POST', self.root + "/usuario/ajax", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 2) {
            }
            if (xhr.readyState == 4 && xhr.status == 200) {
                console.log(file);
            }
        };
        xhr.send(form);
    };

};