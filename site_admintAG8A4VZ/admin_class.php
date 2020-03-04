<?PHP
/**
 *管理组管理
 * @author 郭志成 (go_tit@163.com)
 * 
 */
define('THIS_RIGHT', 'adminClass');
define('IS_INC', TRUE);
require_once './common/common.php';

$arrStatus = array(0=>array('name'=>'禁用','color'=>'red'),1=>array('name'=>'正常','color'=>'green'));

$tpl->assign('arrStatus', $arrStatus);

$cm = new adminClassManage($_GET,$_POST,$db, $tpl);
$html = $cm->execute();

$tpl->display('admin_class.tpl');

class adminClassManage
{
    var $_db = null;
    var $_get = array();
    var $_post = array();
    var $_action = '';
    var $_html = '';
    private $tpl = null;
    
    function __construct($_G, $_P, &$db, &$tpl)
    {
        $this->_db = $db;
        $this->_get = $_G;
        $this->_post = $_P;
        $this->tpl = $tpl;
        
        if (isset($this->_get['action']) && !empty($this->_get['action']))
        {
            $this->_action = $this->_get['action'];
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
    
    function _add()
    {
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if (empty($this->_post['ac_name']))
            {
                notice('缺少名称!');
                exit;
            }
            else 
            {
                //添加
                //过滤
                $post = addslashes_deep($this->_post);
                
                $sql = 'SELECT COUNT(*) FROM t_admin_class WHERE ac_name=\'' . $this->_post['ac_name'] . '\'';
                if ($this->_db->getOne($sql) > 0)
                {
                    ScriptAlert('已经存在此管理组名称!');
                    exit;
                }
                
                $arrInsert = array();
                $arrInsert['ac_name'] = $post['ac_name'];
                $arrInsert['ac_memo'] = $post['ac_memo'];
                $arrInsert['ac_status'] = intval($post['ac_status']);
                $arrInsert['ac_date'] = date('Y-m-d H:i:s');
                
                $this->_db->autoExecute('t_admin_class', $arrInsert);
                
                if ($this->_db->insert_id())
                {
                    success('添加成功！');
                    exit;
                }
                else 
                {
                    notice('添加失败!');
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $ac_id = intval($this->_post['ac_id']);
            $post = addslashes_deep($this->_post);
            if ($this->_post['ac_name'] == '')
            {
                notice('名称为空!');
                exit;
            }
            if ($ac_id == 0)
            {
                notice('出错!');
                exit;
            }
            
            $sql = 'SELECT COUNT(*) c FROM `t_admin_class` WHERE `ac_name`=\''.$this->_post['ac_name'].'\' AND ac_id!='.$ac_id;
            if ($this->_db->getOne($sql))
            {
                notice('已经存在此管理组名称!');
                exit;
            }
            
            $arrInsert = array();
            $arrInsert['ac_name'] = $post['ac_name'];
            $arrInsert['ac_memo'] = $post['ac_memo'];
            $arrInsert['ac_status'] = intval($post['ac_status']);
            $arrInsert['ac_date'] = date('Y-m-d H:i:s');
            
            $this->_db->autoExecute('t_admin_class', $arrInsert, 'UPDATE', 'ac_id=' . $ac_id);
            if ($this->_db->affected_rows())
            {
                success('修改成功!');
                exit;
            }
            else 
            {
                notice('修改失败!');
                exit;
            }
            
        }
        else 
        {
            $id = intval($this->_get['id']);
            
            $sql = 'SELECT * FROM t_admin_class WHERE ac_id=' . $id;
            
            $info = $this->_db->getRow($sql);
            
            if (!$info)
            {
                notice('找不到此管理组！');
                exit;
            }
            
            $this->tpl->assign('info', $info);
        }
    }
    
    function _delete()
    {
        if ($this->_get['type'] == 'get' && $this->_get['id'] > 0)
        {
            $id = intval($this->_get['id']);
            $sql = 'SELECT COUNT(*) c FROM `t_admin` WHERE `ad_deleted`=0 AND `ac_id`=' . $id;
            if ($this->_db->getOne($sql) > 0)
            {
                notice('此管理组下还有管理员，请先删除！');
                exit;
            }
            
            $sql = 'DELETE FROM `t_admin_class` WHERE `ac_id`='.$id;
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
            notice('参数错误!');
            exit;
        }
    }
    
    function _default()
    {
        $sql = 'SELECT * FROM `t_admin_class`';
        
        $list = $this->_db->getAll($sql);
        $this->tpl->assign('list', $list);
    }
}