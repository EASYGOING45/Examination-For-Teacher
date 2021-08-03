define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'yexam/exam/index',
                    add_url: 'yexam/exam/add',
                    edit_url: 'yexam/exam/edit',
                    del_url: 'yexam/exam/del',
                    multi_url: 'yexam/exam/multi',
                    table: 'exam',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url+"?type=2",
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},

                        {field: 'subject_name', title: __('科目名称'), operate:false},
                        {field: 'exam_name', title: __('考试名称')},
                        {field: 'num', title: __('题目数量')},
                        {field: 'score', title: __('考试总分')},
                        {field: 'givetime', title: __('考试时长（分钟）')},
                        {field: 'start_date', title: __('开始时间')},
                        {field: 'end_date', title: __('结束时间')},
                        {field: 'status', title: "是否显示",operate: false, formatter: Table.api.formatter.toggle},
                        {field: 'sort', title: __('排序'),operate:false},
                        {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', align:'center', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    title: __('抽题组卷'),
                                    text:'抽题组卷',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-microchip',
                                    extend:'data-area=\'["1100px","700px"]\'',
                                    url: 'yexam/exam/sel_question?exam_id={id}'
                                },
                                {
                                    name: 'detail',
                                    title: __('学员考试记录'),
                                    text:'学员考试记录',
                                    classname: 'btn btn-xs btn-default btn-dialog',
                                    extend:'data-area=\'["1100px","700px"]\'',
                                    icon: 'fa fa-file-text-o',
                                    url: 'yexam/exam/user_log?exam_id={id}'
                                },
                                {
                                    name: 'detail',
                                    title: __('考题管理'),
                                    text:'考题管理',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    extend:'data-area=\'["1000px","700px"]\'',
                                    icon: 'fa fa-th-list ',
                                    url: 'yexam/exam/question?exam_id={id}'
                                }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $(".nav-tabs a").click(function(){

                table.bootstrapTable('refresh', {url:'yexam/exam/index?type='+$(this).attr('data-id')});
            })

        },
        user_log: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "yexam/exam/user_log?exam_id="+$("#table").attr('data-id'),
                    table: 'user_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch:false,
                search:false,
                columns: [
                    [
                        {field: 'pm', title: __('排名')},
                        {field: 'nickname', title: __('姓名')},
                        {field: 'mobile', title: __('手机号')},
                        {field: 'score', title: __('分数')},
                        {field: 'answer_num', title: __('答题数')},
                        {field: 'right_num', title: __('正确数')},
                        {field: 'error_num', title: __('错题数')}, {
                        field: 'operate', align:'center', title: __('Operate'), table: table, events: Table.api.events.operate,
                        buttons: [
                            {

                                name: 'send',
                                title: __('错题记录'),
                                text: '错题记录',
                                classname: 'btn btn-xs btn-warning btn-dialog',
                                icon: 'fa fa-file-text-o',
                                url: 'yexam/exam/error_user_log?id={id}'
                            },
                            {

                                name: 'detail',
                                title: __('下载考卷'),
                                text: '下载考卷',
                                classname: 'btn btn-xs btn-primary',
                                icon: 'fa fa-download',
                                url: 'yexam/exam/pdf?id={id}',
                                success: function (data, ret) {
                                    Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                },
                            }],
                        formatter: Table.api.formatter.operate
                    }
                    ]
                ]
            });

            var page = 1;
            var limit = 5;
            var loading;

            function  create_pdf(url,data){
                $.ajax({
                    type:'POST',
                    url:url,
                    data:data,
                    dataType:'json',
                    success:function (res) {
                        if(res.code == 0){
                            if(res.data.length > 0){
                                data.page++;
                                create_pdf(url,data);
                            }else{

                                top.location.href=res.url
                                layer.close(loading);
                            }
                        }else{
                            if(res.error){
                                layer.msg("暂无记录")
                            }
                            layer.close(loading);

                        }
                    }
                })
            }

            $(document).on('click','.btn-pdf',function(){

                loading = layer.msg('生成中...', {
                    icon: 16
                    ,shade: 0.01
                    ,time:0
                });

                var url = 'yexam/exam/pdf_all';
                create_pdf(Fast.api.fixurl(url),{exam_id:$("#table").attr('data-id'),page:page,limit:limit});

            })
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        error_user_log: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "yexam/exam/error_user_log?id="+$("#table").attr('data-id'),
                    table: 'user_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch:false,
                search:false,
                columns: [
                    [

                        {field: 'question_name', title: '题目名称',align:'left'},
                        {field: 'type_name', title: '类型'},
                        {field: 'right_answer', title: '正确答案'},
                        {field: 'user_answer', title: '学员答案'}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        question: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "yexam/exam/question?exam_id="+$("#table").attr('data-id'),
                    del_url: "yexam/exam/delquestion?exam_id="+$("#table").attr('data-id'),
                    table: 'exam',
                }
            });

            var table = $("#table");

            $(document).on('click', '.btn-question-add', function () {
                var url = "yexam/exam/addquestion?exam_id="+$(this).attr('data-id');
                Fast.api.open(url, __('Add'), {
                    callback:function () {
                        table.bootstrapTable('refresh');
                    }
                });
            });

            require(['upload'], function(Upload){

                Upload.api.plupload($(".btn-import_excel"), function(data, ret){
                    Layer.msg('导入中', {
                        icon: 16
                        ,shade: 0.1,
                        time:100000
                    });
                    $.ajax({
                        type:'POST',
                        url:'yexam/exam/import',
                        data:{file:data.url,exam_id:$(".btn-import_excel").attr('data-id')},
                        success:function (response) {
                            if(response.code){
                                Toastr.success("导入成功");
                                table.bootstrapTable('refresh');
                            }else{
                                Toastr.error(response.msg);
                                table.bootstrapTable('refresh');
                            }
                            Layer.closeAll()
                        }
                    })
                }, function(data, ret){

                });
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('题目ID'),operate:false},
                        {field: 'question_name', align:'left', title: __('题目名称'),operate:'like'},
                        {field: 'unit_id', title: __('题目来源'),operate:false,formatter: Controller.api.formatter.unit_id},
                        {field: 'type', title: __('题目类型'), searchList: {"3":__('判断'),"2":__('多选'),"1":__('单选')}, formatter: Table.api.formatter.normal},
                        {field: 'right_answer', title: __('正确答案'),operate:false,formatter: Controller.api.formatter.type},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    hidden:function(row){return row.unit_id == 0?false:true},
                                    name: 'detail',
                                    title: __('编辑'),
                                    text:'编辑',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil ',
                                    url: 'yexam/exam/editquestion',
                                }],

                            formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        addquestion:function(){
            $("#c-type").change(function(){
                switch ($(this).val()) {
                    case "1":
                        $(".danxuan,.xuanze").show();
                        $(".panduan,.duoxuan").hide();
                        break;
                    case "2":
                        $(".duoxuan,.xuanze").show();
                        $(".danxuan,.panduan").hide();
                        break;

                    case "3":
                        $(".panduan").show();
                        $(".danxuan,.duoxuan,.xuanze").hide();

                        break;
                }

            })
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
                Toastr.success("成功");//这个可有可无
            }, function(data, ret){
                Toastr.success("失败");
            });
        },
        editquestion: function () {
            $("#c-type").change(function(){
                switch ($(this).val()) {
                    case "1":
                        $(".danxuan,.xuanze").show();
                        $(".panduan,.duoxuan").hide();
                        break;
                    case "2":
                        $(".duoxuan,.xuanze").show();
                        $(".danxuan,.panduan").hide();
                        break;

                    case "3":
                        $(".panduan").show();
                        $(".danxuan,.duoxuan,.xuanze").hide();

                        break;
                }

            })

            Controller.api.bindevent();

        },
        add: function () {
            Controller.api.bindevent();
        },
        sel_question:function(){
            require(['jstree'], function () {
            var type = 1;
            var tid = "";
            Table.api.init({
                extend: {
                    has_url: "yexam/exam/has_ques?exam_id="+$("#table").attr('data-id'),
                }
            });

            $(document).on('click', '.btn-ques-add', function () {
                var ids = Table.api.selectedids(table);

                var exam_id = $(this).attr('data-id');

                $.ajax({
                    type:'POST',
                    url:'yexam/exam/add_exam_ques',
                    data:{exam_id:exam_id,ids:ids},
                    success:function (response) {
                        if(response.code){
                            Toastr.success("添加成功");
                            $.ajax({
                                type:'POST',
                                url:'yexam/exam/refresh_tree',
                                data:{exam_id:exam_id},
                                dataType:'json',
                                success:function (response) {
                                    if(response.code){
                                        $('#channeltree').jstree(true).settings.core.data=response.chanelList;
                                        $('#channeltree').jstree(true).refresh();
                                    }else{
                                        Toastr.error(response.msg);
                                    }
                                }
                            })

                        }else{
                            Toastr.error(response.msg);
                        }
                        table.bootstrapTable('refresh');
                    }
                })
            });

            $(document).on('click', '.btn-ques-del', function () {
                var ids = Table.api.selectedids(table);

                var exam_id = $(this).attr('data-id');
                $.ajax({
                    type:'POST',
                    url:'yexam/exam/del_exam_ques',
                    data:{exam_id:exam_id,ids:ids},
                    success:function (response) {
                        if(response.code){
                            Toastr.success("移除成功");
                            $.ajax({
                                type:'POST',
                                url:'yexam/exam/refresh_tree',
                                data:{exam_id:exam_id,ids:ids},
                                dataType:'json',
                                success:function (response) {
                                    if(response.code){

                                        $('#channeltree').jstree(true).settings.core.data=response.chanelList;
                                        $('#channeltree').jstree(true).refresh();
                                    }else{
                                        Toastr.error(response.msg);
                                    }
                                }
                            })
                        }else{
                            Toastr.error(response.msg);
                        }
                        table.bootstrapTable('refresh');
                    }
                })
            });

            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.has_url+"&type="+type,
                pk: 'id',
                sortName: 'id',
                search:false,
                commonSearch:false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true,formatter: function (value,row) {
                                if(row.checked){
                                    return{checked:true}
                                }else{
                                    return{checked:false}
                                }

                            }},
                        {field: 'id', title: __('ID')},
                        {field: 'question_name', align:'left', title: __('题目名称')},
                        {field: 'type', title: __('题目类型'), searchList: {"3":__('判断'),"2":__('多选'),"1":__('单选')}, formatter: Table.api.formatter.normal},
                        {field: 'right_answer', title: __('正确答案'),formatter: Controller.api.formatter.type},
                    ]
                ]
            });
            $(function(){
                $(".tab_btn").click(function(){
                    type = $(this).attr('data-value');
                    $(this).parent().siblings().removeClass('active');
                    $(this).parent().addClass('active');

                    if(type == 3){
                        $(".btn-unit-add,.btn-type-add,.btn-ques-add").hide();
                        $(".btn-ques-del").show();
                    }else if(type == 2){
                        $(".btn-unit-add,.btn-type-add,.btn-ques-add").show();

                        $(".btn-ques-del").hide();
                    }else if(type == 1){
                        $(".btn-unit-add,.btn-type-add,.btn-ques-add").hide();
                        $(".btn-ques-del").show();
                    }
                    table.bootstrapTable('refresh', {url: $.fn.bootstrapTable.defaults.extend.has_url+"&type="+type+"&tid="+tid,pageNumber:1});
                })
            })

            Table.api.bindevent(table);

                $(document).on("click", "#expandall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
                $('#channeltree').on("changed.jstree", function (e, data) {
                    if(data.node){
                        tid = data.node.id;
                        table.bootstrapTable('refresh', {url: $.fn.bootstrapTable.defaults.extend.has_url+"&type="+type+"&tid="+tid,});
                    }
                    return false;
                });
                $('#channeltree').jstree({
                    "themes": {
                        "stripes": true
                    },
                    "checkbox": {
                        "keep_selected_style": false,
                    },
                    "types": {
                        "channel": {
                            "icon": "fa fa-th",
                        },
                        "list": {
                            "icon": "fa fa-list",
                        },
                        "link": {
                            "icon": "fa fa-link",
                        },
                        "disabled": {
                            "check_node": false,
                            "uncheck_node": false
                        }
                    },
                    'plugins': ["types"],
                    "core": {
                        "multiple": true,
                        'check_callback': true,
                        "data": Config.channelList
                    }
                });




            });
        },
        edit: function () {

            $(document).on('change', '#c-type', function () {
                if($(this).val() == 5){
                    $(".form-time").show();
                    $(".form-time input").attr('data-rule','required');

                }else{
                    $(".form-time").hide();
                    $(".form-time input").val('');
                    $(".form-time input").attr('data-rule','*');
                    $(".form-time input").attr('aria-invalid','');

                }
            });

            Controller.api.bindevent();
        },
        api: {
            formatter:{
                unit_id: function (value, row, index) {
                    return value == 0?'本场专属':'章节抽取';
                },
                type: function (value, row, index) {
                    return row['type'] == 3?value == 1?'正确':'错误':value;
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});