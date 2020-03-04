<?PHP
/**
 * 
 * @author 郭志成 (go_tit@163.com)
 * @since 2013-01-26
 */
define('THIS_RIGHT', 'ad_ad');
require 'common.inc.php';

$cm = new typeManage($_GET, $_POST, $db, $tpl);

$html = $cm->execute();

$tpl->display('ad/ad.tpl');

class typeManage
{
    /**
     * 
     * @var cls_mysql
     */
    var $_db        = null;
    var $_get       = array();
    var $_post      = array();
    var $_action    = '';
    var $_html      = '';
    /**
     * 
     * @var Smarty
     */
    private $tpl    = null;
    
    function __construct($_G, $_P, &$db, &$tpl)
    {
        $this->_db = $db;
        $this->_get = $_G;
        $this->_post = $_P;
        $this->tpl = $tpl;
        if (isset($_REQUEST['action']) && !empty($_REQUEST['action']))
        {
            $this->_action = $_REQUEST['action'];
        }
    }
    
    function execute()
    {
        $function = '_' . $this->_action;
        if (method_exists($this, $function))
        {
            $this->$function();          
        }
        else 
        {
            $this->_default();
        }
        return $this->_html;
    }
    
    function _ajax()
    {
        global $arrStatus;
        
        $type = $_REQUEST['type'];
        
        if ($type == 'status')
        {
            $id = intval($_REQUEST['id']);
            
            $arrRet = array(
                'error'=>1,
                'msg'=>'',
                'id'=>$id,
                'html'=>'',
            );
            do {
                $sql = 'SELECT `ad_status` FROM `t_ad` WHERE `ad_id`=' . $id;
                if ($info = $this->_db->getRow($sql))
                {
                    $targetStatus = $info['ad_status']?0:1;
                    $sql = 'UPDATE `t_ad` SET `ad_status`=' . $targetStatus . ' WHERE `ad_id`=' . $id;
                    $this->_db->query($sql);
                    if ($this->_db->affected_rows() > 0)
                    {
                        $arrRet['error'] = 0;
                        $arrRet['html'] = '<font color="' . $arrStatus[$targetStatus]['color'] . '">' . $arrStatus[$targetStatus]['name'] . '</font>';
                    }
                }
                else
                {
                    $arrRet['msg'] = '找不到！';
                    break;
                }
            } while (0);
            
            echo json_encode($arrRet);
        }
        exit;
    }
    
    function _add()
    {
        
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $name = $this->_post['ad_name'];
            if (empty($name))
            {
                notice('缺少名称', -1, '出错');
                exit;
            }
            else 
            {
                //添加
                //过滤
                $post = addslashes_deep($this->_post);
                $name = $post['ad_name'];
                $acKey = $post['ad_key'];
                //查找是否已经存在
                $sql = 'SELECT COUNT(*) c FROM `t_ad` WHERE `ad_key`=\'' . $acKey . '\'';
                if ($this->_db->getOne($sql) > 0)
                {
                    notice('此标识已经存在', -1, '出错');
                    exit;
                }
                
                $arrInsert = array();
                $arrInsert['ad_name']       = $post['ad_name'];
                $arrInsert['ad_key']        = $post['ad_key'];
                $arrInsert['ad_redirect_url']        = $post['ad_redirect_url'];
                $arrInsert['ad_site']       = $post['ad_site']; //站点
                $arrInsert['ad_memo']       = $post['ad_memo'];
                $arrInsert['ad_status']     = intval($post['ad_status'])?1:0;
                $arrInsert['ad_add_time'] = time();
                
                $this->_db->autoExecute('t_ad', $arrInsert);
                
                if ($this->_db->insert_id())
                {
                    
                    if ($post['submit_type'] == 'save')
                    {
                        success('添加广告成功！', '?');
                    }
                    else
                    {
                        success('添加广告成功！');
                    }
                    exit;
                }
                else 
                {
                    error('添加失败！');
                    exit;
                }
            }
        }
        else 
        {
            
        }
    }
    
    function _edit()
    {
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $ad_id = intval($this->_post['ad_id']);
            $post = addslashes_deep($this->_post);
            if ($post['ad_name'] == '')
            {
                notice('名称为空!');
                exit;
            }
            if ($ad_id == 0)
            {
                error('出错!');
                exit;
            }
            $name = $post['ad_name'];
            
            $arrData = array();
            
            $arrData['ad_name']       = $post['ad_name'];
            $arrData['ad_site']       = $post['ad_site'];
            $arrData['ad_redirect_url']       = $post['ad_redirect_url'];
            $arrData['ad_memo']       = $post['ad_memo'];
            $arrData['ad_order']      = intval($post['ad_order']);
            $arrData['ad_status']     = intval($post['ad_status'])?1:0;
            $arrData['ad_last_time'] = time();
            
            $this->_db->autoExecute('t_ad', $arrData, 'UPDATE', 'ad_id=' . $ad_id);
            if ($this->_db->affected_rows())
            {
                if ($this->_post['submit_type'] == 'save')
                {
                    success('修改成功！', '?');
                }
                else
                {
                    success('修改成功！');
                }
                exit;
            }
            else 
            {
                notice('修改失败!');
                exit;
            }
            
        }
        elseif (!empty($this->_get['id']))
        {
            $id = intval($this->_get['id']);
            
            $sql = 'SELECT * FROM `t_ad` WHERE ad_id=' . $id;
            
            $info = $this->_db->getRow($sql);
            
            if ($info)
            {
                $this->tpl->assign('info', $info);
            }
            else 
            {
                notice('找不到此广告！');
                exit;
            }
        }
        else 
        {
            $this->_default();
        }
    }
    
    function _delete()
    {
        //如果是POST,处理
        if ($_SERVER['REQUEST_MOTHOD'] == 'POST')
        {
            
        }
        elseif ($this->_get['type'] == 'get' && $this->_get['id'] > 0)
        {
            $id = intval($this->_get['id']);
            $id = intval($this->_get['id']);
            //查找下面还有没有菜单
            $sql = 'UPDATE `t_ad` SET `ad_deleted`=1 WHERE `g_id`=' . $id;
            
            $this->_db->query($sql);
            if ($this->_db->affected_rows() > 0)
            {
                success('删除成功!');
                exit;
            }
            else 
            {
                notice('删除失败!');
                exit;
            }
        }
        else 
        {
            error('参数错误!');
            exit;
        }
    }
    
    function _default()
    {
        //给出所有列表
        $per = 25;
        $p = key_exists('p', $_REQUEST)?intval($_REQUEST['p']):1;
        if ($p < 1)
        {
            $p =1;
        }
        $where = ' WHERE 1=1 AND ad_deleted=0';
        
        if (strlen($_REQUEST['name']) > 0)
        {
            $where .= ' AND `ad_name` LIKE \'%' . addslashes_deep($_REQUEST['name']) . '%\'';
        }
        if (strlen($_REQUEST['key']) > 0)
        {
            $where .= ' AND `ad_key`=\'' . addslashes_deep($_REQUEST['key']) . '\'';
        }
        $sql = 'SELECT COUNT(*) FROM `t_ad` ' . $where;
        $totalCount = intval($this->_db->getOne($sql));
        
        $sql = 'SELECT * 
        	FROM `t_ad` ' . $where  . ' LIMIT ' . ($p - 1)*$per . ',' . $per;
        
        $list = $this->_db->getAll($sql);
        if ($list)
        {
            foreach ($list as $key=>$value)
            {
                $list[$key]['ad_add_time'] = date('Y-m-d H:i:s', $value['ad_add_time']);
            }
        }
        $pager = new pager(array('total'=>$totalCount, 'perpage'=>$per));
        $pageInfo = $pager->show(1);
        
        $this->tpl->assign('list', $list);
        $this->tpl->assign('pageInfo', $pageInfo);
    }
}