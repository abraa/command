<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/3/15 17:26
 * ====================================
 * File: Model.php
 * ====================================
 */

namespace app\admin\controller;


use app\admin\BaseController;
use app\common\support\MysqlSupport;

class  Database extends BaseController{

    public function index(){
        if ($this->request->isAjax()) {
            $page = input('page',1);
            $count = input('rows',0);
            $data=db()->query("SHOW TABLE STATUS LIKE'".config('prefix')."%'");
            if($data){
                $data = collection($data)->toArray();
                foreach($data as &$val){
                    $val['Index_length'] =calc($val['Index_length']);
                    $val['Data_length'] =calc($val['Data_length']);
                    $val['id'] =$val['Name'];
                }
            }
            if(!empty($count)){
                $total = count($data);
                $start = $count*($page-1);
                $data = array_slice($data,$start,$count);
                return [
                    'total' => (int)$total,
                    'rows' => (empty($data) ? [] : $data),
                    'pagecount' => ceil($total / $count),
                ];
            }else{
                return $data;
            }

        }
        return $this->fetch($this->template);
    }

    public function save(){

        $id = input('id');
        $name = input('Name');
        $type = input('type','dir','trim');   //默认目录
        if(empty($id)){             //添加
        }
    }

    public function form(){
        $tableName= input("get.id");
        if ($this->request->isPost()) {
            $params = input('post.');
            $orderBy = isset($params['sort']) ? trim($params['sort']) . ' ' . trim($params['order']) : '';
            $page = isset($params['page']) && $params['page'] > 0 ? intval($params['page']) : 1;
            $pageSize = isset($params['rows']) && $params['rows'] > 0 ? intval($params['rows']) : 0;
            $db = db()->table($tableName);
            $db->order($orderBy);
            if(empty($pageSize)){
                $data = $db->select();
                if (false !== $data) {
                    $data = collection($data)->toArray();
                }
                $total = count($data);
            }else{
                $result = $db->paginate($pageSize, false, ['page' => $page])->toArray();
                $total = $result['total'];
                $data = $result['data'];
            }
            return [
                'total' => (int)$total,
                'rows' => (empty($data) ? [] : $data),
                'pagecount' => empty($pageSize) ? 1: ceil($total / $pageSize),
            ];
        }
        $column = db()->query("desc ".$tableName);
        $fields = array_column($column,'Field');
        $this->assign('fields',$fields);
        return $this->fetch();
    }

    public function delete(){
        $id = input('id');
        if(!empty($id)){
            $tables = explode('|',$id);
            $db = db();
            foreach($tables as $table){
                $db->execute('drop table '.$table);
            }
        }
        $this->success('删除成功');
    }

    /**
     * 执行sql语句
     */
    public function query(){
        $query = input('query',null,'trim');
        if(!empty($query)){
            $res = db()->execute($query);
            $this->success('执行成功', null, $res);
        }else{
            $this->error('命令为空');
        }
    }

    /**
     * 创建或修改数据表
     */
    public function table(){
        if ($this->request->isAjax()) {
            $id = input('id');
            $name = input('Name', null, 'strtolower');
            $Comment = input('Comment', '', 'trim');
            $Engine = input('Engine', 'InnoDB', 'trim');
            if (empty($name)) {
                $this->error('缺少数据表名称');
            }
            $db = db();
            if (empty($id)) {             //创建数据库
                $createSql = <<<s
CREATE TABLE `{name}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `text` varchar(255) DEFAULT NULL COMMENT '名称',
  `order` smallint(5) DEFAULT '0' COMMENT '推荐顺序',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE={engine} AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COMMENT='{comment}';
s;
                $sql = str_replace(['{name}', '{engine}', '{comment}'], [$name, $Engine, $Comment], $createSql);
                $db->execute($sql);
            } else if (strcmp($id, $name)) {                   //修改数据表名称
                $sql = "rename table '" . $id . "' to '" . $name . "';";
                $db->execute($sql);
            }
            return $this->success('保存成功');
        }
        return $this->fetch($this->template);
    }

    /**
     * 创建或修改数据表字段
     */
    public function savefield(){
        $tableName =  input('get.id');
        if(empty($tableName)){
            return false;
        }
        $input = input('post.');
        try{
//            if(isset($input['isNewRecord']) && $input['isNewRecord']==true){   //新增
            if(empty($input['id'])){   //新增
                $result =  MysqlSupport::addColumn($tableName,$input);

            }else{              //修改
                $result = MysqlSupport::alertColumn($tableName,$input);
            }
        }catch (\Exception $e){
            $this->error($e->getMessage());
//            return['isError'=>1,"msg"=>$e->getMessage()];
        }

        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    /**
     * 显示当前表字段列表
     */
    public function showfield(){
        $id = input('id');
        return MysqlSupport::showColumn($id);
    }

    /**
     * 删除当前字段
     */
    public function deletefield(){
        $fieldName = input('post.id');
        $tableName = input('get.id');
        if(MysqlSupport::dropColumn($tableName,$fieldName)){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }


    /**
     * 显示索引
     */
    public function showindex(){
        $id = input('id');    //table
        return MysqlSupport::showIndex($id);
    }

    /**
     * 创建或修改数据表索引
     */
    public function saveindex(){
        $tableName =  input('get.id');
        if(empty($tableName)){
            return false;
        }
        $input = input('post.');
        try{
            $result =  MysqlSupport::addIndex($tableName,$input,$input['id']);
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    /**
     * 删除当前字段
     */
    public function deleteindex(){
        $indexName = input('post.id');
        $tableName = input('get.id');
        if(MysqlSupport::dropIndex($tableName,$indexName)){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 显示触发器列表
     */
    public function showtriggers(){
        $id = input('id');
        return MysqlSupport::showTriggers($id);
    }

    /**
     * 创建或修改触发器
     */
    public function savetriggers(){
        $tableName =  input('get.id');
        if(empty($tableName)){
            return false;
        }
        $input = input('post.');
        try{
            $result =  MysqlSupport::addTriggers($tableName,$input,$input['id']);
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        if($result){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    /**
     * 删除触发器
     */
    public function deletetriggers(){
        $triggersName = input('post.id');
        if(MysqlSupport::dropTriggers($triggersName)){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    /**
     * 备份数据库
     */
    public function backup(){
        $path = RUNTIME_PATH.'mysql';
        $database = config('database')['database'];
        $info = "-- ----------------------------\r\n";
        $info .= "-- 日期：".date("Y-m-d H:i:s",time())."\r\n";
        $info .= "-- MySQL - 5.5.52-MariaDB : Database - ".$database."\r\n";
        $info .= "-- ----------------------------\r\n\r\n";
        $info .= "CREATE DATAbase IF NOT EXISTS `".$database."` DEFAULT CHARACTER SET utf8 ;\r\n\r\n";
        $info .= "USE `".$database."`;\r\n\r\n";

        //检查目录是否存在并创建
        if(makeDir($path)){
            $file_name = $path.DIRECTORY_SEPARATOR.$database.'-'.date("Y-m-d",time()).'.sql';

            // 检查文件是否存在
            if(file_exists($file_name)){
                $this->error("数据备份文件已存在！") ;
            }
            file_put_contents($file_name,$info,FILE_APPEND);

            //查询数据库的所有表
            $db = db();
            $result =$db->query('show tables');
            foreach ($result as $k=>$v) {
                 //查询表结构
                $val = $v['Tables_in_'.$database];
                $sql_table = "show create table ".$val;
                $res = $db->query($sql_table);
                //print_r($res);exit;
                $info_table = "-- ----------------------------\r\n";
                $info_table .= "-- Table structure for `".$val."`\r\n";
                $info_table .= "-- ----------------------------\r\n\r\n";
                $info_table .= "DROP TABLE IF EXISTS `".$val."`;\r\n\r\n";
                $info_table .= $res[0]['Create Table'].";\r\n\r\n";
                //查询表数据
                $info_table .= "-- ----------------------------\r\n";
                $info_table .= "-- Data for the table `".$val."`\r\n";
                $info_table .= "-- ----------------------------\r\n\r\n";
                file_put_contents($file_name,$info_table,FILE_APPEND);
                $sql_data = "select * from ".$val;
                $data = $db->query($sql_data);
                //print_r($data);exit;
                $count= count($data);
                //print_r($count);exit;
                if($count<1) continue;
                foreach ($data as $key => $value){
                    $sqlStr = "INSERT INTO `".$val."` VALUES (";
                    foreach($value as $v_d){
                        $v_d = str_replace("'","\'",$v_d);
                        $sqlStr .= "'".$v_d."', ";
                    }
                    //需要特别注意对数据的单引号进行转义处理
                    //去掉最后一个逗号和空格
                    $sqlStr = substr($sqlStr,0,strlen($sqlStr)-2);
                    $sqlStr .= ");\r\n";
                    file_put_contents($file_name,$sqlStr,FILE_APPEND);
                }
                $info = "\r\n";
                file_put_contents($file_name,$info,FILE_APPEND);
            }
            $this->success("数据备份完成！") ;
        }
    }

    /**
     * 导出选中数据表
     */
    public function export(){
        $path = RUNTIME_PATH.'mysql';
        $tables = input('id');
        if(!empty($tables) && makeDir($path)){
            $tables = explode('|',$tables);
            $file_name = $path . DIRECTORY_SEPARATOR . $tables[0] . '-' . date("Y-m-d", time()) . '.sql';
            // 检查文件是否存在
            if(file_exists($file_name)){
                $this->error("数据备份文件已存在！") ;
            }
            $db = db();
            foreach($tables as $val){
                $sql_table = "show create table ".$val;
                $res = $db->query($sql_table);
                //print_r($res);exit;
                $info_table = "-- ----------------------------\r\n";
                $info_table .= "-- Table structure for `".$val."`\r\n";
                $info_table .= "-- ----------------------------\r\n\r\n";
                $info_table .= "DROP TABLE IF EXISTS `".$val."`;\r\n\r\n";
                $info_table .= $res[0]['Create Table'].";\r\n\r\n";
                //查询表数据
                $info_table .= "-- ----------------------------\r\n";
                $info_table .= "-- Data for the table `".$val."`\r\n";
                $info_table .= "-- ----------------------------\r\n\r\n";
                file_put_contents($file_name,$info_table,FILE_APPEND);
                $sql_data = "select * from ".$val;
                $data = $db->query($sql_data);
                //print_r($data);exit;
                $count= count($data);
                //print_r($count);exit;
                if($count<1) continue;
                foreach ($data as $key => $value){
                    $sqlStr = "INSERT INTO `".$val."` VALUES (";
                    foreach($value as $v_d){
                        $v_d = str_replace("'","\'",$v_d);
                        $sqlStr .= "'".$v_d."', ";
                    }
                    //需要特别注意对数据的单引号进行转义处理
                    //去掉最后一个逗号和空格
                    $sqlStr = substr($sqlStr,0,strlen($sqlStr)-2);
                    $sqlStr .= ");\r\n";
                    file_put_contents($file_name,$sqlStr,FILE_APPEND);
                }
                $info = "\r\n";
                file_put_contents($file_name,$info,FILE_APPEND);
            }
            header("Content-Type: text/plain"); //指定下载文件类型的
            header("Content-Disposition:attachment;filename=".$tables[0]);
            //指定下载文件的描述信息
            header("Content-Length:".filesize($file_name));  //指定文件大小的
            readfile($file_name);//将内容输出，以便下载。
            @unlink($file_name);
        }
    }
}