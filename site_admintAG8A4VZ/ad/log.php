<?PHP
/**
 * 
 * @author 郭志成 (go_tit@163.com)
 * @since 2013-01-26
 */
define('THIS_RIGHT', 'ad_log');
require 'common.inc.php';

$cm = new typeManage($_GET, $_POST, $db, $tpl);

$html = $cm->execute();

$tpl->display('ad/log.tpl');

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
    
    function _default()
    {
        //给出所有列表
        $per = 25;
        $p = key_exists('p', $_REQUEST)?intval($_REQUEST['p']):1;
        if ($p < 1)
        {
            $p =1;
        }
        $where = ' WHERE 1=1';
        
        if (strlen($_REQUEST['ad_id']) > 0)
        {
            $where .= ' AND ad_id=' . intval($_REQUEST['ad_id']);
        }
        if (strlen($_REQUEST['start_date']) > 0)
        {
            $where .= ' AND al_add_time>=' . strtotime($_REQUEST['start_date'] . ' 00:00:00');
        }
        if (strlen($_REQUEST['end_date']) > 0)
        {
            $where .= ' AND al_add_time<=' . strtotime($_REQUEST['end_date'] . ' 23:59:59');
        }
        
        $sql = 'SELECT COUNT(*) FROM `t_ad_log` ' . $where;
        $totalCount = intval($this->_db->getOne($sql));
        
        $sql = 'SELECT * 
        	FROM `t_ad_log` ' . $where  . ' ORDER BY al_id DESC LIMIT ' . ($p - 1)*$per . ',' . $per;
        
        $list = $this->_db->getAll($sql);
        if ($list)
        {
            foreach ($list as $key=>$value)
            {
                $list[$key]['al_add_time'] = date('Y-m-d H:i:s', $value['al_add_time']);
            }
        }
        $pager = new pager(array('total'=>$totalCount, 'perpage'=>$per));
        $pageInfo = $pager->show(1);
        
        $this->tpl->assign('list', $list);
        $this->tpl->assign('pageInfo', $pageInfo);
    }
}