<?php
/**
 * Created by TCL
 * User: Administrator
 * Date: 2019/3/13
 * Time: 11:04
 */
namespace Lib\Traits;
trait Common
{
    /**
     * 判断是否为json
     * @param $string
     * @return bool
     */
    public  function isJson($string)
    {
        return ((is_string($string)
            && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
    }

    /**
     * 是否为命令行请求
     *
     * @return bool
     */
    public function isCli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }

    /**
     * 获取IP
     *
     * @return mixed
     */
    public function clientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 获取内存的使用量
     *
     * @return string
     */
    function memoryUsage()
    {
        return round(memory_get_usage() / 1024 / 1024, 2).'MB';
    }

}