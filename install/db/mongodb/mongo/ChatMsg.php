<?php
/**
 * ====================================
 * 聊天模型
 * ====================================
 * Author: 9004396
 * Date: 2017-11-20 17:09
 * ====================================
 * Project: ggzy
 * File: ChatMsg.php
 * ====================================
 */

namespace app\common\model\mongo;

use app\common\model\Mongo;

class ChatMsg extends Mongo
{


    protected $name = 'message';


    /**
     * mongoDB原生扩展查询
     * @param $filter
     * @param array $Options
     * @return mixed
     */
    public function mongoQuery($filter,$Options = []){
        $query = new \MongoDB\Driver\Query($filter, $Options);
        return $this->query($this->getTable(),$query);
    }

    /**
     * mongoDB原生扩展聚合查询
     * @param array $pipeline
     * @return mixed
     */
    public function mongoAggregate($pipeline=[]){

        $cmd = new \MongoDB\Driver\Command([
            'aggregate' => $this->getTable(),
            'pipeline' => $pipeline,
        ]);
        $result = $this->command($cmd,null);
        return $result[0]['ok'] ? $result[0]['result'] : false;
    }

    /**
     * 取当前用户和其他人最后一条聊天记录
     * @param $key
     * @param array $to_key
     * @return array
     */
    public function getUserLastChatMsg($key,$to_key=[]){
        if(empty($to_key)||empty($key)){
            return [];
        }
        $result =  $this->mongoAggregate([
            ['$match'=>[                                                                          //$key发给$to_key的 or $to_key发个$key的  最后一条
                '$or'=>[['key'=>$key,'to_key'=>['$in'=>$to_key]],
                    ['key'=>['$in'=>$to_key],'to_key'=>$key ]
                ]
            ]],
            ['$group'=>['_id'=>['key'=>'$key',"to_key"=>'$to_key'],'addtime'=>['$max'=>'$addtime'],'msg'=>['$last'=>'$msg'] ]]
        ]);
        $arr = [];
        foreach($result as $val){                                                       //遍历处理结果为 ['$key'=>['msg'=>'msggggg','addtime'=>12345],..]
            $_id = $val['_id'];
            if(isset($arr[$_id['key']])){
                if($val['addtime'] > $arr[$_id['key']]['addtime']){
                    $arr[$_id['key']]['msg'] = $val['msg'] ;
                    $arr[$_id['key']]['addtime'] = $val['addtime'] ;
                }
            }else{
                $arr[$_id['key']] = ['msg'=>$val['msg'],'addtime'=>$val['addtime']];
            }
            if(isset($arr[$_id['to_key']])){
                if($val['addtime'] > $arr[$_id['to_key']]['addtime']){
                    $arr[$_id['to_key']]['msg'] = $val['msg'] ;
                    $arr[$_id['to_key']]['addtime'] = $val['addtime'] ;
                }
            }else{
                $arr[$_id['to_key']] = ['msg'=>$val['msg'],'addtime'=>$val['addtime']];
            }
        }
        return $arr;
    }
}