/**
 * Created by Aditya on 2/22/2016.
 */
/// <reference path="../typings/browser/ambient/jquery/jquery.d.ts"/>
(function ($) {

    $.fn.tabullet = function (options) {
        var defaults = {
            rowClass: '',
            columnClass: '',
            tableClass: 'table',
            textClass: 'form-control',
            editClass: 'btn btn-success',
            deleteClass: 'btn btn-danger',
            saveClass: 'btn btn-success',
            deleteContent: 'Delete',
            editContent: '修改',
            saveContent: '保存',
            action: function () {
            }
        };
        options = $.extend(defaults, options);
        var columns = $(this).find('thead > tr th');
        var idMap = $(this).find('thead > tr').first().attr('data-tabullet-map');
        var metadata = [];
        columns.each(function (i, v) {

            metadata.push({
                map: $(v).attr('data-tabullet-map'),
                readonly: $(v).attr('data-tabullet-readonly'),
                type: $(v).attr('data-tabullet-type')
            });
        });
        var index = 1;
        var data = options.data;
        var version = options.version;
        $(data).each(function (i, v) {
            v._index = index++;
        });
        var table = this;
        $(table).find("tbody").remove();
        var tbody = $("<tbody/>").appendTo($(this));
        $(data).each(function (i, v) {
            var ii = i;
            var vv = v;
            var tr = $("<tr/>").appendTo($(tbody)).attr('data-tabullet-id', v[idMap]);
            var rowNum = parseInt($(metadata).length - 2);
            $(metadata).each(function (mi, mv) {
                if (mv.type === 'del') {
                    var td = $("<td/>")
                        .html('<button class="' + options.deleteClass + '">' + options.deleteContent + '</button>')
                        .attr('data-tabullet-type', mv.type)
                        .appendTo(tr);
                    td.find('button').click(function (event) {
                        tr.remove();
                        options.action('delete', $(tr).attr('data-tabullet-id'));
                    });
                }
                else if (mv.type === 'edit') {
                    var td = $("<td/>")
                        .html('<button class="' + options.editClass + '">' + options.editContent + '</button>')
                        .attr('data-tabullet-type', mv.type)
                        .appendTo(tr);
                    td.find('button').click(function (event) {

                        if ($(this).attr('data-mode') === 'edit') {
                            var editData = [];
                            var rowParent = td.closest('tr');
                            var rowChildren = rowParent.find('input');

                            var indexsss = rowNum;
                            var indexNum = 0;

                            // console.log(vv);
                            $(rowChildren).each(function (ri, rv) {
                                indexNum += parseInt($(rv).val());
                                // console.log(parseInt(indexNum));
                                var staMedicine = ( parseInt(indexNum) - parseInt($(rv).val())+1 );
                                var endMedicine = parseInt(indexNum);
                                // console.log('sta:'+staMedicine + ' end:'+endMedicine);
                                if(indexNum <= indexsss){
                                    //判断版本
                                    if(version == 'V1'){
                                        if(parseInt($(rv).val()) > 3){
                                            alert('第'+ (parseInt(ii)+1) +'层，第'+ staMedicine +'-' + endMedicine +'货道不允许合并');
                                            for(var j=0; j<parseInt($(rv).val()); j++){
                                                editData[$(rv).attr('name')] = 1;
                                            }
                                        }else if(parseInt($(rv).val()) < 1){
                                            editData[$(rv).attr('name')] = 1;
                                            alert('输入值不能小于0');
                                        }else{
                                            if( (staMedicine%3 == 0 && (endMedicine%3) > 0) || ((staMedicine+1)%3 == 0 && parseInt($(rv).val()) == 3 )){
                                                alert('第'+ (parseInt(ii)+1) +'层，第'+ staMedicine +'-' + endMedicine +'货道不允许合并');
                                                for(var j=0; j<parseInt($(rv).val()); j++){
                                                    editData[$(rv).attr('name')] = 1;
                                                }
                                            }else{
                                                editData[$(rv).attr('name')] = parseInt($(rv).val());
                                            }
                                        }
                                    }else{
                                        editData[$(rv).attr('name')] = parseInt($(rv).val());
                                    }
                                }
                            });

                            editData[idMap] = $(rowParent).attr('data-tabullet-id');
                            console.log(editData);
                            
                            var minEditDataLength = 0;
                            for (var i = 1; i <= parseInt(editData.length-1) ; i++) {
                                minEditDataLength += parseInt(editData[i]);
                            }
                            
                            var forNum = rowNum - minEditDataLength;

                    
                            for (var i = 0; i < forNum; i++) {
                                editData[editData.length] = 1;
                            }
                            
                            options.action('edit', editData);
                            return;
                        }
                        $(this).removeClass(options.editClass).addClass(options.saveClass).html(options.saveContent)
                            .attr('data-mode', 'edit');
                        var rowParent = td.closest('tr');
                        var rowChildren = rowParent.find('td');
                        $(rowChildren).each(function (ri, rv) {
                            if ($(rv).attr('data-tabullet-type') === 'edit' || $(rv).attr('data-tabullet-type') === 'delete') {
                                return;
                            }
                            var mapName = $(rv).attr('data-tabullet-map');
                            if ($(rv).attr('data-tabullet-readonly') !== 'true') {
                                $(rv).html('<input type="text" name="' + mapName + '" value="' + v[mapName] + '" class="' + options.textClass + '"/>');
                            }
                        });
                    });
                }
                else {
                    if(mi == 0){
                        var td = $("<td/>").html(v[mv.map])
                        .attr('data-tabullet-map', mv.map)
                        .attr('colspan', 1)
                        .attr('data-tabullet-readonly', mv.readonly)
                        .attr('data-tabullet-type', mv.type)
                        .appendTo(tr);
                    }else{
                        if(v[mv.map]){
                            var td = $("<td/>").html(v[mv.map])
                            .attr('data-tabullet-map', mv.map)
                            .attr('colspan', v[mv.map])
                            .attr('data-tabullet-readonly', mv.readonly)
                            .attr('data-tabullet-type', mv.type)
                            .appendTo(tr);
                        }
                    }
                }
            });
        });
    };
}(jQuery));
