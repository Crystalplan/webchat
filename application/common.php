<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

//
define('DOMAIN', 'http://chat.crystalsky.top/');
define('FILES_DOMAIN', 'http://files.crystalsky.top/');
// 定义数据表常量
define('DB_USER', 'user');
define('DB_GROUP', 'group');
define('DB_USER_GROUP', 'user_group');
define('DB_FGROUP', 'fgroup');
define('DB_USER_FRIEND', 'user_friend');

// 应用公共文件

if (!function_exists('trimArray')) {
    /**
     * 数组过滤
     * @param mix
     * @return mix
     */
    function trimArray($data)
    {
        if (!is_array($data)) {
            return trim($data);
        }
        return array_map('trimArray', $data);
    }
}

if (!function_exists('aes_encrypt')) {
    /**
     * AES 加密
     * @param $plain_text
     * @return string
     */
    function aes_encrypt($plain_text)
    {
        $encrypted_data = openssl_encrypt($plain_text, 'aes-128-cbc', config('aes_key'), OPENSSL_RAW_DATA, config('aes_iv'));

        return base64_encode($encrypted_data);
    }
}

if (!function_exists('aes_decrypt')) {
    /**
     * AES 解密
     * @param $str
     * @return string
     */
    function aes_decrypt($str)
    {
        $decrypted = openssl_decrypt(base64_decode($str), 'aes-128-cbc', config('aes_key'), OPENSSL_RAW_DATA, config('aes_iv'));

        return $decrypted;
    }
}

if (!function_exists('redis')) {
    /**
     * 实例化redis对象
     */
    function redis()
    {
        static $redis;
        if (!isset($redis)) {
            try {
                $redis = new Redis();
                $config = config('redis');
                $redis->connect($config['host'], $config['port'], $config['timeout']);
                if (!empty($config['auth'])) {
                    $redis->auth($config['auth']);
                }
                if (!empty($config['select'])) {
                    $redis->select($config['select']);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return $redis;
    }
}

if (!function_exists('getIp')) {
    /**
     * 获取IP地址
     */
    function getIp()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        elseif (isset($_SERVER["HTTP_CLIENT_IP"]))
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        elseif ($_SERVER["REMOTE_ADDR"])
            $ip = $_SERVER["REMOTE_ADDR"];
        elseif (getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        elseif (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        elseif (getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else
            $ip = "Unknown";
        return $ip;
    }
}

if (!function_exists('makeSign')) {
    /*
     * 生成签名,将集合M内非空参数值的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串stringA。
     * @param   array   $data
     * @param   string  $key
     * @return  string  sing
     */
    function makeSign($data, $key)
    {
        $str = "";
        ksort($data);
        foreach ($data as $k => $v) {
            if ("sign" !== $k && "" !== $v) {
                $str .= $k . "=" . $v . "&";
            }
        }
        $str .= "key=" . $key;
        $sign = md5($str);
        return $sign;
    }
}