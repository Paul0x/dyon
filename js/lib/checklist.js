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
 *  File: checklist.js
 *  Type: Javascript Library
 *  =====================================================================
 */

checkList = function() {
    this.root = $("#dir-root").val();
    this.checklistobj;

    this.init = function(callback_function) {
        var self = this;
        self.loadCheckListForm();
        self.bindButtons();
        self.checklistobj = new Object();
        self.checklistobj.items = new Array();
        self.callback_function = callback_function;
    };

    this.submitCheckListObject = function() {
        var self = this;
        self.errorMessage("clear");
        var title = $("#checklist-add-form input[name='checklist-title']").val();
        if (title === "" || title.length === 0) {
            self.errorMessage("O título da checklist não pode ficar vazio.");
            return;
        }
        
        self.checklistobj.title = title;
        
        if(self.checklistobj.items.length < 1) {
            self.errorMessage("Você precisa inserir ao menos 01 item na checklist.");
            return;
        } 
        self.callback_function(self.checklistobj);
    };

    this.bindButtons = function() {
        var self = this;
        $("#checklist-add-more-items").die().live("click", function() {
            self.addMoreItems();
        });

        $("#checklist-add-form div .checklist-item").die().live("hover", function() {
            var id = this.id.split("-")[2];
            $("#checklist-add-form div .checklist-item .remove-button[removeid='" + id + "'").css("display", "block");
        }).live("mouseleave", function() {
            var id = this.id.split("-")[2];
            $("#checklist-add-form div .checklist-item .remove-button[removeid='" + id + "'").css("display", "none");
        });

        $("#checklist-add-form div .checklist-item .remove-button").die().live("click", function() {
            self.removeCheckListItem(this);
        });
        
        $("#checklist-submit-button").die().live("click", function() {
            self.submitCheckListObject();
        });
    };

    this.removeCheckListItem = function(element) {
        var self = this;
        var id = $(element).attr("removeid");
        $("#checklist-item-" + id).remove();
        self.checklistobj.items.splice(id, 1);
        self.recreateItens();
        console.log(self.checklistobj.items);

    };

    this.loadCheckListForm = function() {
        var html = "<div class='ajax-minibox ajax-minibox-form-1' id='checklist-add-form'>";
        html += "<div class='ajax-box-title'>Criar Checklist</div>";
        html += "<div class='item'>";
        html += "<label>Título da Checklist</label>";
        html += "<input type='text' name='checklist-title' placeholder='Ex: Adicionar compras ao evento.' />";
        html += "</div>";
        html += "<div class='item'>";
        html += "<label><i class='fa fa-check-square-o'></i> Itens da Checklist</label>";
        html += "<div id='checklist-add-items'>";
        html += "<input type='text' name='checklist-item' counter='1' placeholder='Item' />";
        html += "</div>";
        html += "<div id='checklist-add-more-items'><i class='fa fa-plus' aria-hidden='true'></i> ADICIONAR</div>";
        html += "</div>";
        html += "<div class='submit'>";
        html += "<input type='button' value='Criar' class='btn-01' id='checklist-submit-button' />";
        html += "<input type='button' value='Fechar' class='btn-03 ajax-close-box' />";
        html += "</div>";
        html += "</div>";
        loadAjaxBox(html);
    };

    this.addMoreItems = function() {
        var self = this;
        self.errorMessage("clear");
        var count = parseInt($("input[name='checklist-item']").length);
        if (isNaN(count) || count > 1) {
            self.errorMessage("Não é possível adicionar mais de um item por vez.");
            return;
        }

        var checklist_item = $("input[name='checklist-item']");
        if ($(checklist_item).val() === "" || $(checklist_item).val() === undefined) {
            self.errorMessage("O item da checklist não pode estar vazio.");
            return;
        }

        self.checklistobj.items.push($(checklist_item).val());
        $(checklist_item).remove();
        self.recreateItens();

    };

    this.recreateItens = function() {
        var self = this;
        var html = "";
        $.each(self.checklistobj.items, function(idx, item) {
            html += "<div class='checklist-item' id='checklist-item-" + idx + "'><i class='fa fa-square-o'></i> | " + escapeHtml(item) + "<span removeid='" + idx + "' class='remove-button'>Remover</span></div>";
        });
        html += "<input type='text' name='checklist-item' placeholder='Item' />";
        $("#checklist-add-items").html(html);

    };

    this.errorMessage = function(message) {
        if (message === "clear") {
            $("#checklist-form-error").remove();
        } else {
            $("#checklist-add-form .submit").append("<div id='checklist-form-error'>" + message + "</div>");
        }
    };

};