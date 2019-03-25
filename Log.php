<?php
namespace Lib;

/**
 * Class Log
 * @package Lib
 */
class Log
{
    /**
     * @var null
     */
    protected $msg         = '';

    /**
     * @var bool
     */
    protected $status      = false;

    /**
     * @var int
     */
    protected $code        =  0;

    /**
     * @var null|string
     */
    private $log_file    =  null;

    /**
     * Log constructor.
     * @param array $param
     */
    function __construct($param=[])
    {
        if(! empty($param['log_file']))
        {
            $this->log_file = $param['log_file'];
        }
        else
        {
            $this->log_file = './logs/' . date('Y-m-d') . '.txt';
        }

    }

    /**
     * 记录日志
     * @param $msg
     * @param null $file
     * @return bool|int
     */
    public function write($msg, $file = null)
    {
        ini_set('date.timezone','Asia/Shanghai');

        $path = $this->touch_file($file);

        $msg = date('Y-m-d H:i:s') . '   ==>   ' . $msg . PHP_EOL . PHP_EOL;

        return file_put_contents($path, $msg, FILE_APPEND);
    }

    /**
     * 生成日志文件
     *
     * @param $file
     * @return string
     */
    private function touch_file($file)
    {
        if(empty($file))
        {
            $file = $this->log_file;
        }

        $dirname = dirname($file);
        if(! file_exists($dirname))
        {
            mkdir($dirname ,0777 , true);
        }

        return $file;
    }

    /**
     * 读/写 错误信息
     * @param $msg
     * @param bool $status
     * @param int $code
     * @return array|null
     */
    public function error_msg($msg='', $status=false, $code = 0)
    {
        if($msg)
        {
            $this->msg          = $msg;
            $this->status       = $status;
            $this->code         = $code;
        }
        else
        {
            return $this->msg;
        }
    }

}