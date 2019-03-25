<?php
/**
 * Created by TCL
 * User: Administrator
 * Date: 2018/6/20
 * Time: 14:41
 */

namespace Lib;

use Lib\Email;
use Lib\Log;

/**
 * Class Handler
 * @package Lib
 */
class Capture
{
    /**
     * @var bool
     */
    private $catch_exception = false;

    /**
     * @var bool
     */
    private $catch_error      = false;

    /**
     * @var null
     */
    private static $error_msg = [];

    /**
     * @var bool
     */
    private $notify             = true;

    /**
     * Error constructor.
     * @param array $config
     */
    public function __construct($config=[])
    {
        if (count($config) > 0)
        {
            foreach ($config as $key => $val)
            {
                if (isset($this->$key))
                {
                    $this->$key = $val;
                }
            }
        }

        if($this->catch_exception == true)
        {
            set_exception_handler([$this, 'exception_handler']);
        }

        if($this->catch_error == true)
        {
            ini_set('log_errors', 1);
            set_error_handler([$this, 'error_handler']);
        }
    }

    /**
     * 异常捕获
     *
     * @param $exception
     * @return bool
     */
    public function  exception_handler($exception)
    {
        $info = $this->info_format($exception);

        //写入日志
        $this->log($info);

        //发送邮件
        $this->notify && $this->send_email('未捕获异常', $info);

        exit('有异常未捕获，程序中断');
    }

    /**
     * 错误捕获
     *
     * @param $error_type
     * @param $error_msg
     * @param $error_file
     * @param $error_line
     * @return void
     */
    public function error_handler($error_type, $error_msg, $error_file, $error_line)
    {
        switch ($error_type) {

            case 2:
                $error_type = 'E_WARNING';
                break;

            case 8:
                $error_type = 'E_NOTICE';
                break;

            case 256:
                $error_type = 'E_USER_ERROR';
                break;

            case 2047:
                $error_type = 'E_ALL';
                break;

            case 2048:
                $error_type = 'E_STRICT';
                break;

            default:
                break;
        }
        $info  = 'catch_type: error';
        $info .= ',error_file: '  . $error_file;
        $info .= ', error_type: ' . $error_type;
        $info .= ', error_line: ' . $error_line;
        $info .= ', error_msg:  ' . $error_msg;

        //写入日志
        $this->log($info);

        //报错后发送错误
        $this->notify && $this->send_email('运行错误', $info);

        exit('运行错误，程序中断');
    }

    /**
     * 消息格式化
     *
     * @param $exception
     * @return string
     */
    private function info_format($exception)
    {
        if($exception instanceof \Exception)
        {
            $info  = 'catch_type: exception'          ;
            $info .= ', file: ' . $exception->getFile();
            $info .= ', line: ' . $exception->getLine();
            $info .= ', msg: ' . $exception->getMessage();
        }
        else
        {
            $info  = $exception;
        }

        return $info;
    }

    /**
     * 发送邮件
     *
     * @param string $message
     * @param string $subject
     * @return bool
     */
    private function send_email($subject='', $message='')
    {
        $email = new Email();
        if(($email->send_email(['subject' => $subject, 'message' => $message])) == false)
        {
            $this->log(PHP_EOL . $email->error_msg());
            return false;
        }

        return true;
    }

    /**
     * @param $msg
     */
    private function log($msg)
    {
        $log = new Log();
        $log->write($msg);
    }

    /**
     *  读取/保存 错误信息
     * @param string $msg
     * @param bool $status
     * @param int $code
     * @return bool|null
     */
    public static function error_msg($msg='', $status=false, $code = 0)
    {
        if($msg)
        {
            self::$error_msg = ['msg' => $msg, 'code' => $code, 'status' => $status];
            return true;
        }
        else
        {
            return self::$error_msg['msg'] ?? '';
        }
    }

}