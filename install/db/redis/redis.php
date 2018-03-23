<?php
config('redis',[
    'host'       => '192.168.148.204',
    'port'       => 6379,
    'password'   => 'Kg0JCsHaiP1fsoBJDD',
    'select'     => 1,
    'timeout'    => 0,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',
]);
$redisConfig = config('redis');
$redisConfig['type'] = 'redis';
config('cache', [
    'type'   => 'complex',
    'redis'  => $redisConfig,
]);
$redis = \think\Cache::store('redis');
if (!empty($checkToken)) {
    return $redis->get('talkExpireToken:' . $checkToken);
}
$randString = getRandom(32) . time();
$md5 = strtoupper(md5($randString));
$redis->set('talkExpireToken:' . $md5, $value, $expire);
?>