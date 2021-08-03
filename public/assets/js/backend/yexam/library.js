define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'yexam/library/index',
                    add_url: 'yexam/library/add',
                    edit_url: 'yexam/library/edit',
                    del_url: 'yexam/library/del',
                    multi_url: 'yexam/library/multi',
                    table: 'yexam_library',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'library.weigh',
                sortOrder:'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate: false},
                        {field: 'library_name', title: __('题库名称'),operate:'like'},
                        {field: 'subject.subject_name', title: __('科目名称'),operate: false},
                        {field: 'num', title: __('题目数量'),operate: false},
                        {field: 'status', title: "是否显示",operate: false, formatter: Table.api.formatter.toggle},
                        {field: 'weigh', title: __('排序'),operate: false},
                        {field: 'createtime', title: __('添加时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: __('题目管理'),
                                    text:'题目管理',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    extend:'data-area=\'["1100px","700px"]\'',
                                    url: 'yexam/library/question?library_id={id}'
                                }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            $("#c-type").change(function(){
                if($(this).val() == 2){
                    $(".year").show();
                }else{
                    $(".year").hide();
                }
            })
            Controller.api.bindevent();
        },
        edit: function () {
            $("#c-type").change(function(){
                if($(this).val() == 2){
                    $(".year").show();
                }else{
                    $(".year").hide();
                }
            })
            Controller.api.bindevent();
        },
        question: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "yexam/library/question?library_id="+$("#table").attr('data-id'),
                    del_url: "yexam/library/delquestion?library_id="+$("#table").attr('data-id'),
                    table: 'exam',
                }
            });

            var table = $("#table");

            $(document).on('click', '.btn-question-add', function () {
                var url = "yexam/library/addquestion?library_id="+$(this).attr('data-id');
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
                        url:'yexam/library/import',
                        data:{file:data.url,library_id:$(".btn-import_excel").attr('data-id')},
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
                        {field: 'type', title: __('题目类型'), searchList: {"3":__('判断'),"2":__('多选'),"1":__('单选')}, formatter: Table.api.formatter.normal},
                        {field: 'right_answer', title: __('正确答案'),operate:false,formatter: Controller.api.formatter.right_answer},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    hidden:function(row){return row.unit_id == 0?false:true},
                                    name: 'detail',
                                    title: __('编辑'),
                                    text:'编辑',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil ',
                                    url: 'yexam/library/editquestion',
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
        api: {
            formatter:{
                right_answer: function (value, row, index) {
                    return row.type == 3?(value==1?'对':'错'):value;
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});