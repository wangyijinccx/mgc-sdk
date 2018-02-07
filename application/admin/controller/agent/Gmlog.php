<?php
namespace app\admin\controller\agent;

use app\admin\model\UserOperateLog;
use app\common\controller\Backend;

/**
 * 申请扶持
 *
 * @icon fa fa-circle-o
 */
class Gmlog extends Backend {
    protected $model                   = null;
    protected $node_model              = null;
    protected $admin_node_model        = null;
    protected $admin_plat_model        = null;
    protected $admin_game_model        = null;
    protected $admin_game_server_model = null;
    protected $admin_users_model       = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = model('GmLog');
        $this->node_model = new \huooa\model\Nodemodel();
        $this->admin_node_model = new \app\admin\model\Node();
        $this->admin_plat_model = new \app\admin\model\Plat();
        $this->admin_game_model = new \app\admin\model\Game();
        $this->admin_game_server_model = new \app\admin\model\GameServer();
        $this->admin_users_model = new \app\admin\model\Users();
    }

    /**
     * 查看
     */
    public function index() {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            /* 权限限制  */
            $_can_edit = 2;
            $_now_node_id = isset($this->node_id) ? $this->node_id : 0;
            $_prefix = \think\Config::get('database.prefix');
            $_map = [];
            if ($this->node_model->isAdmin($_now_node_id)) {/* todo超级管理员判断 */
                // $total = $this->model
                $total = \think\Db::name('gm_log')
                                  ->where($where)
                                  ->order($sort, $order)
                                  ->count();
                // $list = $this->model
                $list = \think\Db::name('gm_log')
                                 ->where($where)
                                 ->order($sort, $order)
                                 ->limit($offset, $limit)
                                 ->select();
            } else {
                if ($this->node_model->isChief($_now_node_id) || $this->node_model->isPromoter($_now_node_id)) {
                    /* 推广员和团长不显示编辑按钮 */
                    $_can_edit = 1;
                }
                /* 当不是超级管理员时 只能看到祖先节点是自己的  */
                // $_map['n.ancestor'] = $_now_node_id;
                $_map = " EXISTS (select distance from ".$_prefix
                        ."node_relation as m where m.descendant=".$_prefix
                        ."gm_log.node_id AND m.ancestor =".$_now_node_id.")";
                /*$total = $this->model
                    ->table($_prefix.'gm_log l')*/
                $total = \think\Db::name('gm_log')
                    // ->alias('l')
                    //  ->join($_prefix.'node_relation n ', ' n.descendant=l.node_id')
                                  ->where($where)
                                  ->where($_map)
                                  ->order($sort, $order)
                                  ->count();
//                $list = $this->model
//                    ->table($_prefix.'gm_log l')
                $list = \think\Db::name('gm_log')
                    // ->alias('l')
                    //  ->join($_prefix.'node_relation n ', ' n.descendant=l.node_id')
                                 ->where($where)
                                 ->where($_map)
                                 ->order($sort, $order)
                                 ->limit($offset, $limit)
                                 ->select();
            }
            if (is_array($list) && !empty($list)) {
                // $_node_id_str = '';
                //  $_app_id_str = '';
                $_node_id_arr = [];
                $_app_id_arr = [];
                //  $_plat_id_str = '';
                $_plat_id_arr = [];
                //  $_server_id_str = '';
                $_server_id_arr = [];
                $_n_Arr = array_unique($this->newArrayColumn($list, 'node_id'));
                $_a_Arr = array_unique($this->newArrayColumn($list, 'oa_app_id'));
                $_s_Arr = array_unique($this->newArrayColumn($list, 'server_id'));
                $_p_Arr = array_unique($this->newArrayColumn($list, 'plat_id'));
                /* foreach ($list as $item) {
                     if (isset($item['node_id'])) {
                         if (!in_array($item['node_id'], $_n_Arr)) {
                             $_node_id_str .= $item['node_id'].',';
                             $_n_Arr[] = $item['node_id'];
                         }
                     }
                     if (isset($item['oa_app_id'])) {
                         if (!in_array($item['oa_app_id'], $_a_Arr)) {
                             $_app_id_str .= $item['oa_app_id'].',';
                             $_a_Arr[] = $item['oa_app_id'];
                         }
                     }
                     if (isset($item['plat_id'])) {
                         if (!in_array($item['plat_id'], $_p_Arr)) {
                             $_plat_id_str .= $item['plat_id'].',';
                             $_p_Arr[] = $item['plat_id'];
                         }
                     }
                     if (isset($item['server_id'])) {
                         if (!in_array($item['server_id'], $_s_Arr)) {
                             $_server_id_str .= $item['server_id'].',';
                             $_s_Arr[] = $item['server_id'];
                         }
                     }
                 }*/
                /*$_node_id_str = trim($_node_id_str, ',');
                $_app_id_str = trim($_app_id_str, ',');
                $_plat_id_str = trim($_plat_id_str, ',');
                $_server_id_str = trim($_server_id_str, ',');*/
                $_node_id_str = implode(',', $_n_Arr);
                $_app_id_str = implode(',', $_a_Arr);
                $_plat_id_str = implode(',', $_p_Arr);
                $_server_id_str = implode(',', $_s_Arr);
                if ($_node_id_str) {
                    $_node_list = $this->admin_node_model->getNodeUserListByIds($_node_id_str);
                    if (!empty($_node_list)) {
                        $_node_id_arr = $this->newArrayColumn($_node_list, 'username', 'node_id');
                    }
                }
                if ($_app_id_str) {
                    $_app_list = $this->admin_game_model->getGameListByIds($_app_id_str);
                    if (!empty($_app_list)) {
                        $_app_id_arr = $this->newArrayColumn($_app_list, 'name', 'id');
                    }
                }
                if ($_plat_id_str) {
                    $_plat_list = $this->admin_plat_model->getPlatListByIds($_plat_id_str);
                    if (!empty($_plat_list)) {
                        $_plat_id_arr = $this->newArrayColumn($_plat_list, 'nickname', 'id');
                    }
                }
                if ($_server_id_str) {
                    $_server_list = $this->admin_game_server_model->getGameServerListByIds($_server_id_str);
                    if (!empty($_server_list)) {
                        $_server_id_arr = $this->newArrayColumn($_server_list, 'ser_name', 'id');
                    }
                }
                foreach ($list as &$item) {
                    if (isset($item['node_id']) && !empty($_node_id_arr)) {
                        $item['node_name'] = (array_key_exists($item['node_id'], $_node_id_arr))
                            ? $_node_id_arr[$item['node_id']] : $item['node_id'];
                    } else {
                        $item['node_name'] = $item['node_id'];
                    }
                    if (isset($item['oa_app_id']) && !empty($_app_id_arr)) {
                        $item['game_name'] = (array_key_exists($item['oa_app_id'], $_app_id_arr))
                            ? $_app_id_arr[$item['oa_app_id']] : $item['oa_app_id'];
                    } else {
                        $item['game_name'] = $item['oa_app_id'];
                    }
                    if (isset($item['plat_id']) && !empty($_plat_id_arr)) {
                        $item['plat_name'] = array_key_exists($item['plat_id'], $_plat_id_arr)
                            ? $_plat_id_arr[$item['plat_id']] : $item['plat_id'];
                    } else {
                        $item['plat_name'] = $item['plat_id'];
                    }
                    if (isset($item['server_id']) && !empty($_server_id_arr)) {
                        $item['server_name'] = array_key_exists($item['server_id'], $_server_id_arr)
                            ? $_server_id_arr[$item['server_id']] : $item['server_id'];
                    } else {
                        $item['server_name'] = $item['server_id'];
                    }
                    $item['can_edit'] = $_can_edit;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add() {
        if ($this->request->isPost()) {
            $this->code = -1;
            $params = $this->request->post("row/a");
            if ($this->node_model->isAdmin(get_node_id()) && isset($params['node_username'])
                && $params['node_username']
            ) {
                if (false !== strpos($params['node_username'], "'")) {
                    $this->code = -1;
                } else {
                    $_node_info = $this->admin_users_model->getUserInfoByUsername($params['node_username']);
                    if (!empty($_node_info) && isset($_node_info['node_id'])) {
                        $params['node_id'] = $_node_info['node_id'];
                        unset($params['node_username']);
                    }
                }
            }
            if (!isset($params['node_id']) || !$params['node_id']) {
                $params['node_id'] = get_node_id();
            }
            if (isset($params['node_username'])) {
                unset($params['node_username']);
            }
            if (isset($params['oa_app_id']) && $params['oa_app_id']
                && (!isset($params['game_id'])
                    || empty($params['game_id']))
            ) {
                $_game_info = $this->getGameInfo($params['oa_app_id']);
                $params['game_id'] = isset($_game_info['game_id']) ? $_game_info['game_id'] : 0;
            }
            if ($params) {
                $this->model->create($params);
                UserOperateLog::record();
                $this->code = 1;
            }

            return;
        }
        $row['node_id'] = $this->node_model->isAdmin(get_node_id()) ? 1 : 0;
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    /**
     * 审核
     *
     * @param null $ids
     *
     * @return string|void
     */
    public function edit($ids = null) {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $this->code = -1;
            $params = $this->request->post("row/a");
            if ($params) {/* 获取game_id */
                if (isset($params['oa_app_id']) && $params['oa_app_id']
                    && (!isset($params['game_id'])
                        || empty($params['game_id']))
                ) {
                    $_game_info = $this->getGameInfo($params['oa_app_id']);
                    $params['game_id'] = isset($_game_info['game_id']) ? $_game_info['game_id'] : 0;
                }
                if (isset($params['reason']) && isset($params['check_status'])) {
                    if (2 == $params['check_status']) {
                        $params['check_reason'] = $params['reason'];
                    } elseif (3 == $params['check_status']) {
                        $params['fail_reason'] = $params['reason'];
                    }
                }
                if (isset($params['plat_id']) && $params['plat_id']) {
                    $sdk_class = new \huooa\request\Sdk($params['plat_id']);
                    $_save_row_params = $sdk_class->saveCheck($params);/* 过滤多余字段 */
                    $row->save($_save_row_params);
                } else {
                    $row->save($params);
                }
                UserOperateLog::record();
                if (2 == $params['check_status']) {
                    /* 2表示审核通过 对接到sdk平台 */
                    $params['id'] = $ids;
                    $_fail_reason = '参数有误';
                    if (2 == $params['type_id']) {
                        $_check_params = $sdk_class->checkParam($params);
                        if (false === $_check_params) {
                            $this->code = -1;

                            return;
                        }
                        if (!empty($_check_params) && isset($_check_params['code']) && $_check_params['code'] > 300) {
                            $this->code = -1;
                            if (isset($_check_params['msg']) && $_check_params['msg']) {
                                $this->msg = $_check_params['msg'];
                                $_fail_reason .= $_check_params['msg'];
                            }
                            if ($ids) {
                                $params['status'] = 3;
                                $params['fail_reason'] = $_fail_reason;
                                $_save_params = $sdk_class->saveCheck($params);
                                if (false === $_save_params) {
                                    $this->code = -1;

                                    return;
                                }
                                $row->save($_save_params);
                            }
                            UserOperateLog::record();

                            return;
                        }
                        $_sdk_re = $sdk_class->sendFoster($params);
                    } elseif (1 == $params['type_id']) {
                        $_check_params = $sdk_class->checkSendFirst($params);
                        if (false === $_check_params) {
                            $this->code = -1;

                            return;
                        }
                        if (!empty($_check_params) && isset($_check_params['code']) && $_check_params['code'] > 300) {
                            $this->code = -1;
                            if (isset($_check_params['msg']) && $_check_params['msg']) {
                                $this->msg = $_check_params['msg'];
                                $_fail_reason .= $_check_params['msg'];
                            }
                            if ($ids) {
                                $params['status'] = 3;
                                $params['fail_reason'] .= $_fail_reason;
                                $_save_params = $sdk_class->saveCheck($params);
                                \think\Log::write($params, 'error');
                                if (false === $_save_params) {
                                    $this->code = -1;

                                    return;
                                }
                                $row->save($_save_params);
                            }
                            UserOperateLog::record();

                            return;
                        }
                        $_sdk_re = $sdk_class->sendFirst($params);
                    }
                    if (false === $_sdk_re) {
                        \think\Log::write($params, 'error');
                    }
                }
                $this->code = 1;
            }

            return;
        }
        if ($row->node_id) {
            $_node_info = $this->getNodeUserInfo($row->node_id);
            $row->node_name = $this->getValue($_node_info, 'nickname', 'username');
        }
        if ($row->oa_app_id) {
            $_app_info = $this->getGameInfo($row->oa_app_id);
            $row->game_name = $this->getValue($_app_info, 'name', 'id');
        }
        if ($row->plat_id) {
            $_plat_info = $this->getPlatInfo($row->plat_id);
            $row->plat_name = $this->getValue($_plat_info, 'nickname', 'name');
        }
        if ($row->server_id) {
            $_server_info = $this->getGameServerInfo($row->server_id);
            $row->server_name = $this->getValue($_server_info, 'ser_name', 'ser_code');
        }
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "") {
        $this->code = -1;
        if ($ids) {
            $count = $this->model->where('id', 'in', $ids)->delete();
            if ($count) {
                UserOperateLog::record();
                $this->code = 1;
            }
        }

        return;
    }

    /**
     * 批量更新
     */
    public function multi($ids = "") {
        $this->code = -1;
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(array('status')));
                if ($values) {
                    $count = $this->model->where('id', 'in', $ids)->update($values);
                    if ($count) {
                        UserOperateLog::record();
                        $this->code = 1;
                    }
                }
            } else {
                $this->code = 1;
            }
        }

        return;
    }

    /**
     * 获取游戏信息
     *
     * @param int $oa_app_id
     *
     * @return array
     *
     */
    public function getGameInfo($oa_app_id = 0) {
        if (empty($oa_app_id)) {
            return [];
        }

        return $this->admin_game_model->getGameInfo($oa_app_id);
    }

    /**
     * 获取节点用户信息
     *
     * @param int $node_id
     *
     * @return array
     */
    public function getNodeUserInfo($node_id = 0) {
        if (empty($node_id)) {
            return [];
        }

        return $this->admin_node_model->getNodeUserInfo($node_id);
    }

    /**
     * 获取平台信息
     *
     * @param int $node_id
     *
     * @return array
     */
    public function getPlatInfo($plat_id = 0) {
        if (empty($plat_id)) {
            return [];
        }

        return $this->admin_plat_model->getPlatInfo($plat_id);
    }

    /**
     * 获取游戏区服信息
     *
     * @param int $server_id
     *
     * @return array
     *
     */
    public function getGameServerInfo($server_id = 0) {
        if (empty($server_id)) {
            return [];
        }

        return $this->admin_game_server_model->getGameServerInfo($server_id);
    }

    /**
     * 编辑
     */
    public function edit2($ids = null) {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $this->code = -1;
            $params = $this->request->post("row/a");
            if ($params) {
                $row->save($params);
                UserOperateLog::record();
                $this->code = 1;
            }

            return;
        }
        $this->view->assign("row", $row);

        return $this->view->fetch();
    }
}
