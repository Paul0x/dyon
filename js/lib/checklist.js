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

checkList = function () {
    this.root = $("#dir-root").val();
    this.checklistobj;

    this.init = function (callback_function) {
        var self = this;
        self.loadCheckListForm();
        self.bindButtons();
        self.checklistobj = new Object();
        self.checklistobj.items = new Array();
        self.callback_function = callback_function;
    };

    this.submitCheckListObject = function () {
        var self = this;
        self.errorMessage("clear");
        var title = $("#checklist-add-form input[name='checklist-title']").val();
        if (title === "" || title.length === 0) {
            self.errorMessage("O título da checklist não pode ficar vazio.");
            return;
        }

        self.checklistobj.title = title;

        if (self.checklistobj.items.length < 1) {
            self.errorMessage("Você precisa inserir ao menos 01 item na checklist.");
            return;
        }
        self.callback_function(self.checklistobj);
    };

    this.bindButtons = function () {
        var self = this;
        $("#checklist-add-more-items").die().live("click", function () {
            self.addMoreItems();
        });


        $("#checklist-add-form div .checklist-item .remove-button").die().live("click", function () {
            self.removeCheckListItem(this);
        });

        $("#checklist-submit-button").die().live("click", function () {
            self.submitCheckListObject();
        });
    };

    this.removeCheckListItem = function (element) {
        var self = this;
        var id = $(element).attr("removeid");
        $("#checklist-item-" + id).remove();
        self.checklistobj.items.splice(id, 1);
        self.recreateItens();
        console.log(self.checklistobj.items);

    };

    this.loadCheckListForm = function () {
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

    this.addMoreItems = function () {
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
        
        var checkitem = new Object();
        checkitem.title = $(checklist_item).val();
        checkitem.status = 0;

        self.checklistobj.items.push(checkitem);
        $(checklist_item).remove();
        self.recreateItens();

    };

    this.recreateItens = function () {
        var self = this;
        var html = "";
        $.each(self.checklistobj.items, function (idx, item) {
            html += "<div class='checklist-item' id='checklist-item-" + idx + "'><i class='fa fa-square-o'></i> | " + escapeHtml(item.title) + "<span removeid='" + idx + "' class='remove-button'>Remover</span></div>";
        });
        html += "<input type='text' name='checklist-item' placeholder='Item' />";
        $("#checklist-add-items").html(html);

    };

    this.errorMessage = function (message) {
        if (message === "clear") {
            $("#checklist-form-error").remove();
        } else {
            $("#checklist-add-form .submit").append("<div id='checklist-form-error'>" + message + "</div>");
        }
    };

    this.loadCheckList = function (parent_id, updateCallback) {
        var self = this;
        if(isNaN(parent_id)) {
            return;
        }
        
        self.checkChecklistProgress(parent_id, null);
        $("#checklist-wrap .items .item").bind("click", function() {
            self.changeCheckListItem(this, parent_id, updateCallback);
        });
    };
    
    this.checkChecklistProgress = function(parent_id, updateCallback) {
        var items = $("#checklist-wrap .items .item");
        var total_items = 0;
        var completed_items = 0;
        $.each(items, function (idx, item) {
            var item_status = parseInt($(item).attr("status"));
            total_items++;
            if (item_status === 1) {
                completed_items++;
            }
        });

        if (total_items === 0) {
            return;
        }

        var percent = Math.floor((completed_items * 100) / total_items);
        $("#checklist-wrap .status-bar .status-progress").animate({width: percent + "%"});
        var label = " ";
        if (completed_items !== 1) {
            label += " concluídos.";
        }
        else {
            label += " concluído.";
        }
        $("#checklist-wrap .status-counter").html(completed_items + "/" + total_items + label);
        
        if(updateCallback !== null) {
            updateCallback(parent_id, items);
        }
        
    };
    
    this.changeCheckListItem = function(item, parent_id, updateCallback) {
        var self = this;
        var status = parseInt($(item).attr("status"));
        if(status !== 1 && status !== 0) {
            return;
        }
        var title = $(item).attr('title');
        if(status === 1) {
            $(item).attr("status", 0);  
            $(item).html('<div class="not-checked fa fa-square-o"></div><div class="label-title">'+title+'</div>');
        } else {
            $(item).attr("status", 1);   
            $(item).html('<div class="checked fa fa-check-square-o"></div><div class="label-title">'+title+'</div>');         
        }
        
        self.checkChecklistProgress(parent_id, updateCallback);
    };
    
    this.fillChecklist = function(checklist) {
        var self = this;
        self.checklistobj = checklist;
        self.recreateItens();
        $("#checklist-add-form input[name=checklist-title]").val(checklist.title);
        
    };

};