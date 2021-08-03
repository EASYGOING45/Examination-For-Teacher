define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'yexam/question/index?unit_id='+$("#table").attr('data-id'),
                    edit_url: 'yexam/question/edit',
                    del_url: 'yexam/question/del',
                    multi_url: 'yexam/question/multi',
                    table: 'question',
                }
            });

            var table = $("#table");


            $(document).on('click', '.btn-question-add', function () {
                var url = "yexam/question/add?unit_id="+$(this).attr('data-id');
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
                        url:'yexam/question/import',
                        data:{file:data.url,unit_id:$(".btn-import_excel").attr('data-id')},
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
                commonSearch:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'question_name', title: __('题目名称')},

                        {field: 'type', title: __('题目类型'), searchList: {"3":__('判断'),"2":__('多选'),"1":__('单选')}, formatter: Table.api.formatter.normal},
                        {field: 'right_answer', title: __('正确答案'),formatter: Controller.api.formatter.right_answer},
                        {field: 'area', title: __('解析')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        all: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'question/all',
                    add_url: 'question/add',
                    edit_url: 'question/edit',
                    del_url: 'question/del',
                    table: 'question',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'sub_num_id', title: __('系统编号')},
                        {field: 'question_name', title: __('Question_name'),operate:'like'},
                        {field: 'subject_id', title: __('Subject_id')},
                        {field: 'library_id', title: __('Library_id')},
                        {field: 'unit_id', title: __('Unit_id')},
                        {field: 'type', title: __('题目类型'), searchList: {"3":__('判断'),"2":__('多选'),"1":__('单选')}, formatter: Table.api.formatter.normal},
                        {field: 'right_answer', title: __('Right_answer'),operate:false,formatter: Controller.api.formatter.right_answer},
                        {field: 'total_num', title: __('Total_num'),operate:false},
                        {field: 'error_num', title: __('Error_num'),operate:false},
                        {field: 'area', title: __('Area'),operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
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
        edit: function () {
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