define(['jquery', 'bootstrap', 'https://cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js','backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

        },
        examlist: function () {
            
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});