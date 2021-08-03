define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            require(['jstree'], function () {
                var subject_id = "";

                //初始化科目tree
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

                //绑定科目tree点击事件
                $('#channeltree').on("changed.jstree", function (e, data) {
                    if(data.node){
                        $(".btn-unit-add").removeClass('btn-disabled')
                        $(".btn-unit-add").removeClass('disabled')
                        subject_id = data.node.id;
                        table.bootstrapTable('refresh', {url: $.fn.bootstrapTable.defaults.extend.index_url+"?subject_id="+subject_id});
                    }
                    return false;
                });


                // 初始化表格

                Table.api.init({
                    extend: {
                        index_url: 'yexam/unit/index',
                        edit_url: 'yexam/unit/edit',
                        del_url: 'yexam/unit/del',
                        multi_url: 'yexam/unit/multi',
                    }
                });

                var table = $("#table");
                table.bootstrapTable({
                    //url: $.fn.bootstrapTable.defaults.extend.index_url,
                    escape: false,
                    pk: 'id',
                    sortName: 'weigh',
                    pagination: false,
                    commonSearch: false,
                    search:false,
                    columns: [
                        [
                            {field: 'id', title: __('Id')},
                            {field: 'unit_name', title: __('章节名称'),align: 'left'},

                            {field: 'status', title: "是否显示",operate: false, formatter: Table.api.formatter.toggle},
                            {field: 'sort', title: __('排序')},
                            {field: 'createtime', title: __('添加时间'), addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {
                                field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                                buttons: [
                                    {
                                        //hidden:function(row){return row.is_last == 1?false:true},
                                        name: 'detail',
                                        title: __('题目'),
                                        text: __('章节题目'),
                                        classname: 'btn btn-xs btn-primary btn-dialog',
                                        icon: 'fa fa-list',
                                        extend:'data-area=\'["1000px","700px"]\'',
                                        url: 'yexam/question/index?unit_id={id}'
                                    }],
                                formatter: Table.api.formatter.operate
                            }
                        ]
                    ]
                });

                //添加章节按钮
                $(document).on('click', '.btn-unit-add', function () {
                    var url = "yexam/unit/add?subject_id="+subject_id;
                    Fast.api.open(url, __('Add'), {
                        callback:function () {
                            table.bootstrapTable('refresh');
                        }
                    });
                });

                Table.api.bindevent(table);


            });
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
                Toastr.success("成功");//这个可有可无
            }, function(data, ret){
                Toastr.success("失败");
            });
        },
        mutil_price: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter:{
                is_pay: function (value, row, index) {
                    return value?'是':'否';
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});