define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'cxselect', 'template'], function ($, undefined, Backend, Table, Form, cxselect, template) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'agent/gmlog/index',
                    add_url  : 'agent/Gmlog/add',
                    edit_url : 'agent/Gmlog/edit',
                    del_url  : '', /*agent/gmlog/del*/
                    multi_url: 'agent/gmlog/multi',
                    table    : 'agent_gmlog',
                }
            });
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url     : $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                columns : [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'node_name', title: __('Node_id')},
                        {field: 'username', title: __('Username')},
                        {field: 'plat_name', title: __('Plat_id')},
                        {field: 'type_id', title: __('Type_id')},
                        {field: 'game_name', title: __('Oa_app_id')},
                        {field: 'server_name', title: __('Server_id')},
                        {field: 'role_name', title: __('Role_name')},
                        {field: 'game_id', title: __('Game_id')},
                        {field: 'money', title: __('Money')},
                        {
                            field    : 'check_status',
                            title    : __('Check_status'),
                            formatter: function (value, row, index) {
                                if ("2" == row.check_status) {
                                    return '<span class="text-success"><i class="fa fa-circle"></i>审核成功</span>';
                                } else if ("3" == row.check_status) {
                                    return '<span class="text-danger"><i class="fa fa-circle"></i>审核不通过</span>';
                                } else {
                                    return '<span class="text-grey"><i class="fa fa-circle"></i>待审核</span>';
                                }
                            }
                        },
                        {
                            field    : 'status',
                            title    : __('Status'),
                            formatter: function (value, row, index) {
                                if ("2" == row.status) {
                                    return '<span class="text-success"><i class="fa fa-circle"></i>已发放</span>';
                                } else if ("3" == row.status) {
                                    return '<span class="text-danger"><i class="fa fa-circle"></i>拒绝发放</span>';
                                } else {
                                    return '<span class="text-grey"><i class="fa fa-circle"></i>待发送</span>';
                                }
                            }
                        },
                        {field: 'check_reason', title: __('Check_reason')},
                        {field: 'fail_reason', title: __('Fail_reason')},
                        {
                            field    : 'operate',
                            title    : __('Operate'),
                            events   : Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add  : function () {
            Controller.api.bindevent();
            Controller.api.get_Choice_platform_Data();
            Controller.api.selectNode_events();
        },
        edit : function () {
            Controller.api.bindevent();
        },
        api  : {
            bindevent               : function () {
                Form.api.bindevent($("form[role=form]"));
            },
            set_selecteds           : function () {
                $.ajax({
                    url    : '/admin/chiefoashow/gettaskselected',
                    type   : 'GET',
                    success: function (data) {
                        Controller.api.get_gama_list(data.plat_id, function () {
                            Controller.api.get_area(data.game_id, function () {

                                var ret = []; //用户id数组
                                for (var i = 0; i < data.promoter.length; i ++) {
                                    ret.push(data.promoter[i].id);
                                }
                                $("input[name='row[rules]']").val(ret.join(','));
                                $('#Tour_man').val(data.chief_id);
                                $('#Choice_platform').val(data.plat_id);
                                $('#Choice_game').val(data.game_id);
                                $('#Choice_partition').val(data.server_id);

                                $('#c-day2_mem_cnt').val(data.marge_task_id);

                                $('#treeview').jstree().select_node(data.promoter, [true, false]);
                                $('#Choice_game,#Choice_partition,#Tour_man,#Choice_platform').selectpicker('refresh');

                            });
                        });

                    }
                });
            },
            get_Choice_platform_Data: function () {

                $.ajax({
                    url     : '/admin/chiefoashow/getplat',
                    type    : 'POST',
                    dataType: 'json',
                    success : function (data) {
                        var html = template('choice-platform-tpl', {data: data.rows});
                        $('#Choice_platform').html(html);
                        $('#Choice_platform').selectpicker('render');
                        $('#Choice_platform').selectpicker('refresh');
                    }
                });
            },
            selectNode_events       : function () {
                $("#Choice_platform").on('change', function () {
                    var slectTerm = $(this).val();
                    // 平台变化  获取游戏
                    Controller.api.get_gama_list(slectTerm);
                })

                $('#Choice_game').on('change', function () {
                    var slectTerm = $(this).val();
                    // 游戏变化  获取区服
                    Controller.api.get_area(slectTerm);
                })
            },
            get_gama_list           : function (flag, callback) {
                $.ajax({
                    url     : '/admin/chiefoashow/getgame',
                    type    : 'POST',
                    dataType: 'json',
                    data    : {platid: flag},
                    success : function (data) {
                        var html = template('choice-game-tpl', {data: data.rows});
                        $('#Choice_game').html(html);
                        $('#Choice_game').selectpicker('render');
                        $('#Choice_game').selectpicker('refresh');
                        if (callback) {
                            callback();
                        }
                    }
                });
            },
            get_area                : function (flag, callback) {
                $.ajax({
                    url     : '/admin/chiefoashow/getgameser',
                    type    : 'POST',
                    dataType: 'json',
                    data    : {gameid: flag},
                    success : function (data) {
                        var html = template('choice-partition-tpl', {data: data.rows});
                        $('#Choice_partition').html(html)
                        $('#Choice_partition').selectpicker('render');
                        $('#Choice_partition').selectpicker('refresh');
                        if (callback) {
                            callback();
                        }
                    }
                })
            },

            formatter: {
                operate: function (value, row, index) {
                    if (2 == row['can_edit']) {
                        return '<a style="margin-left: 4px" class="btn btn-success btn-xs btn-edit"><i class="fa fa-pencil"></i></a> '
                    } else {
                        return '';
                    }
                },

            },
            events   : {
                operate: $.extend({
                    'click .btn-edit': function (e, value, row, index) {
                        Backend.api.open('agent/Gmlog/edit/ids/' + row['id'], __('edit'));
                    },
                }, Table.api.events.operate),

            }
        }
    };
    return Controller;
});