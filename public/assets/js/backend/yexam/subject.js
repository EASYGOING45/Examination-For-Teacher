define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'yexam/subject/index',
                    add_url: 'yexam/subject/add',
                    edit_url: 'yexam/subject/edit',
                    del_url: 'yexam/subject/del',
                    multi_url: 'yexam/subject/multi',
                    table: 'yexam_subject',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                escape: false,
                pk: 'id',
                sortName: 'weigh',
                sortOrder:'asc',
                search:false,
                pagination: false,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'subject_name', title: __('科目名称'),align: 'left'},
                        {field: 'createtime', title: __('添加时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: "是否显示",operate: false, formatter: Table.api.formatter.toggle},
                        {field: 'weigh', title: "排序",operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });


            $(document).on('click', '.btn-mutil_price', function () {
                var ids = Table.api.selectedids(table);

                var url = "subject/mutil_price?ids="+ids;
                Fast.api.open(url, __('批量改价'), {
                    callback:function () {
                        table.bootstrapTable('refresh');
                    }
                });
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
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