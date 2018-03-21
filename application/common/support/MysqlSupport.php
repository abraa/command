<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/3/17 17:41
 * ====================================
 * File: MysqlSupport.php
 * ====================================
 */

namespace app\common\support;


use think\Db;

class MysqlSupport {


    /**
     *  显示当前表所有字段信息
     * @param $tableName
     * @return array|bool
     */
    static function showColumn($tableName,$where=[]){
        $sql = "SELECT * FROM  information_schema.columns WHERE TABLE_SCHEMA ='".config('database.database')."' AND table_name='".$tableName."' ";
        if(!empty($where)){
            if(is_array($where)){
                foreach($where as $key =>$value){
                    $sql .= " AND ".$key."='".$value."' ";
                }
            }else{
                $sql .= (0==strripos(trim($where),'and'))?' AND ': ' '.$where;
            }
        }
        $res = Db::query($sql);
        if(empty($res)){
            return false;
        }
        $result = [];
        foreach($res as $key=> $filed){
//            if(!isset($result[$key]))$result[$key] = [];
//            $result[$key]['field']
            $result[] = [
                'id'=>$filed['COLUMN_NAME'],
                'name'=>$filed['COLUMN_NAME'],
                'comment'=>$filed['COLUMN_COMMENT'],
                'default'=>$filed['COLUMN_DEFAULT'],
                'type'=>$filed['COLUMN_TYPE'],
                'is_null'=>$filed['IS_NULLABLE']== 'NO' ? 0 : 1
            ];
        }
        return $result;
    }


    /**
     *  添加一个字段
     * @param $tableName
     * @param $column
     * @return array|bool
     */
    static function addColumn($tableName,$column){
        if(strtolower($column['default']) == 'null'){               //默认值
            $default =' ';
        }else{
            $default =" DEFAULT '".$column['default']."'";
        }
        //注: sql只支持简单的int ,varchat ,text 等常用类型. 一些特殊的字段类型会出错
        $sql = "ALTER TABLE `".$tableName."` ADD COLUMN `".strtolower($column['name'])."` ".$column['type']." ".(empty($column['is_null'])?'NOT NULL':'NULL')." ".$default." COMMENT '".$column['comment']."';";
        if(false !== Db::execute($sql)){                                //返回当前字段的字段信息
//            return self::showColumn($tableName,['COLUMN_NAME'=>$column['name']]);
            return true;
        }
        return false;
    }

    /**
     *  添加一个字段
     * @param $tableName
     * @param $column
     * @return array|bool
     */
    static function alertColumn($tableName,$column){
        $sql = "ALTER TABLE `".$tableName."` ";
        if(isset($column['id']) && strtolower($column['id'])<>strtolower($column['name'])){
            $sql .="CHANGE COLUMN `".$column['id']."` `".strtolower($column['name'])."`";
        }else{
            $sql .="MODIFY COLUMN `".strtolower($column['name'])."`";
        }
        if(strtolower($column['default']) == 'null'){               //默认值
            $default =' ';
        }else{
            $default =" DEFAULT '".$column['default']."'";
        }
        $isNull = empty($column['is_null'])?'NOT NULL':'NULL';
        //注: sql只支持简单的int ,varchat ,text 等常用类型. 一些特殊的字段类型会出错
        $sql .=" ".$column['type']." ".$isNull." ".$default." COMMENT '".$column['comment']."';";
        if(false !== Db::execute($sql)){                                //返回当前字段的字段信息
//            return self::showColumn($tableName,['COLUMN_NAME'=>$column['name']]);
            return true;
        }
        return false;
    }

    /**
     *  删除一个字段
     * @param $tableName
     * @param $columnName
     * @return bool
     */
    static function dropColumn($tableName,$columnName){
        if(is_string($columnName)){
            $columnName = explode(",",$columnName);
        }
        $sql = "ALTER TABLE `".$tableName."` ";
        foreach($columnName as &$val){
            $val = " DROP COLUMN `".$val."`";
        }
        $sql .= join(" , ",$columnName);
        if(false !== Db::execute($sql)){
            return true;
        }
        return false;
    }

    /**
     *  显示索引
     * @param $tableName
     * @return array|bool
     */
    static function showIndex($tableName){
        $sql  = "show index from ".$tableName;
        $result = DB::query($sql);
        if(empty($result)){
            return false;
        }
        $ret = [];
        foreach($result as $val){
            if(!isset($ret[$val['Key_name']])){
                $ret[$val['Key_name']] = [
                    'id'=>$val['Key_name'],
                    'Key_name'=>$val['Key_name'],
                    'Non_unique'=>$val['Non_unique'],
                    'Column_name'=>[$val['Seq_in_index']=>$val['Column_name']],
                    'Index_type'=>$val['Index_type']
                ];
            }else{
                $ret[$val['Key_name']]['Column_name'][$val['Seq_in_index']] = $val['Column_name'];
            }
            ksort($ret[$val['Key_name']]['Column_name']);
            $ret[$val['Key_name']]['Column_name_arr'] = array_values($ret[$val['Key_name']]['Column_name']);
        }
        return array_values($ret);
    }

    /**
     * 添加索引
     * @param $tableName
     * @param $data
     * @param string $dropIndex
     * @return bool
     */
    static function addIndex($tableName,$data,$dropIndex=''){
        if(empty($dropIndex)){
            $drop = "";
        }else{
            $drop = "DROP INDEX `".$dropIndex."` ,";
        }
        $sql = "ALTER TABLE `".$tableName."` ".$drop;
        //索引列转数组
        if(is_string($data['Column_name'])){
            $data['Column_name'] = explode(",",$data['Column_name']);
        }
        $data['Column_name'] = array_map(function($var){
            return "`".$var."`";
        },$data['Column_name']);
        //索引关键字
        $indexKey = "";
        if($data['Non_unique'] == 0){           //唯一索引
            $indexKey = 'UNIQUE';
        }
        //添加索引
        $sql.= " ADD ".$indexKey." INDEX `".$data['Key_name']."` (".implode(",",$data['Column_name']).") USING ".$data['Index_type']." ;";
        if(false !== Db::execute($sql)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除索引
     * @param $tableName
     * @param $dropIndex
     * @return bool
     */
    static function dropIndex($tableName,$dropIndex){
        if(empty($tableName) || empty($dropIndex)){
            return false;
        }
        if(is_string($dropIndex)){
            $dropIndex = explode(",",$dropIndex);
        }
        $sql = "ALTER TABLE `".$tableName."` ";
        foreach($dropIndex as $key=>$indexName){
            if("PRIMARY" == strtoupper(trim($indexName))){  //主键不删
               unset($dropIndex[$key]);
            }else{
                $dropIndex[$key] = " DROP INDEX `".$indexName."`";
            }
        }
        $sql .= join(" , ",$dropIndex);
        if(false !== Db::execute($sql)){
            return true;
        }else{
            return false;
        }
    }


    /**
     *  显示触发器
     * @param $tableName
     * @return array|bool
     */
    static function showTriggers($tableName){
        $sql  = "SELECT TRIGGER_NAME,ACTION_STATEMENT,ACTION_TIMING,EVENT_MANIPULATION FROM information_schema.`TRIGGERS` WHERE EVENT_OBJECT_TABLE = '".$tableName."'";
        $result = DB::query($sql);
        foreach($result as &$val){
            $val['id'] = $val['TRIGGER_NAME'];
        }
        if(empty($result)){
            return false;
        }
        return $result;
    }

    /**
     * 添加触发器
     * @param $tableName
     * @param $data
     * @param string $dropTriggers  触发器名称 TRIGGER_NAME
     * @return bool
     */
    static function addTriggers($tableName,$data,$dropTriggers=''){
        if(!empty($dropTriggers)){
            Db::execute("DROP TRIGGER `".$dropTriggers."`;");
        }
        $sql = "CREATE TRIGGER `".$data['TRIGGER_NAME']."` ".$data['ACTION_TIMING']." ".$data['EVENT_MANIPULATION']." ON `".$tableName."` FOR EACH ROW ".$data['ACTION_STATEMENT'].";";
        if(false !== Db::execute($sql)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除触发器
     * @param $dropTriggers     触发器名称 TRIGGER_NAME
     * @return bool
     */
    static function dropTriggers($dropTriggers){
        if(false !==  Db::execute("DROP TRIGGER `".$dropTriggers."`;")){
            return true;
        }else{
            return false;
        }
    }
}