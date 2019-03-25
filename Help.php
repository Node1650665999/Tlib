<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/10
 * Time: 17:20
 */
namespace Lib;

/**
 * Class Help
 * @package Lib
 */
class Help
{
    /**
     * 输出
     *
     * @param $arr
     */
    function p($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
        exit;
    }

    /**
     * 打印反射信息
     * @param $obj
     */
    function fp($obj)
    {
        $reflect = new ReflectionObject($obj);
        Reflection::export($reflect);
        exit;
    }

    /**
     * 验证邮件
     *
     * @param	string
     * @return	bool
     */
    function valid_email($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 手机号码验证
     *
     * @param $phone
     * @return bool
     */
    function valid_phone($phone)
    {
        $preg 		= '/^1[34578]\d{9}$/';
        if(!preg_match($preg, $phone))
        {
            return  false;
        }

        return true;
    }

    /**
     * 文件 Mime Types
     * 根据扩展名获取mime不一定准确，所以这里用finfo函数获取mime
     *
     * @param $file
     * @return string
     */
    function mime_types($file)
    {
        $regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';
        $finfo = @finfo_open(FILEINFO_MIME);
        if (is_resource($finfo))
        {
            $mime = @finfo_file($finfo, $file);
            finfo_close($finfo);

            if (is_string($mime) && preg_match($regexp, $mime, $matches))
            {
                return   $matches[1];
            }
        }

        return 'application/x-unknown-content-type';
    }

    /**
     * 获取文件扩展名
     *
     * @param $filename
     * @return mixed|string
     */
    function file_extension($filename)
    {
        $file_split  = explode('.', $filename);
        if(count($file_split) === 1)
        {
            return '';
        }
        return end($file_split);
    }

    /**
     * 调整日期，防止出现-3月，15月这类日期
     *
     * @param $month
     * @param $year
     * @return array
     */
    function adjust_date($month, $year)
    {
        $date = array();

        $date['month']	= $month;
        $date['year']	= $year;

        while ($date['month'] > 12)
        {
            $date['month'] -= 12;
            $date['year']++;
        }

        while ($date['month'] <= 0)
        {
            $date['month'] += 12;
            $date['year']--;
        }

        if (strlen($date['month']) === 1)
        {
            $date['month'] = '0'.$date['month'];
        }

        return $date;
    }


    /**
     * 调整分页
     *
     * @param $total
     * @param $page
     * @param $per
     * @return array
     */
    function  adjust_paging($total, $page, $per)
    {
        $pages          = ceil($total/$per);
        $page           = ($page <= 0) ? 1 : $page;
        $page           = ($page >= $pages) ? $pages : $page;

        return ['page' => $page, 'per' => $per, 'pages' => $pages];
    }

    /**
     * 生成随机字符串
     *
     * @param $length
     * @return bool|string
     */
    function random_str($length = 32)
    {
        return substr(md5(uniqid(mt_rand(), TRUE)), -$length);
    }

    /**
     * 生成随机数字
     * @param int $length
     * @return bool|string
     */
    function random_num($length = 6)
    {
        return substr(mt_rand(), -$length);
    }

    /**
     * 删除最后一个逗号
     *
     * @param $str
     * @return mixed
     */
    function trim_dot($str)
    {
        return preg_replace('/,(\s*)?$/', '', $str);
    }


    /**
     * 清洗sql
     *
     * @param $sql
     * @return mixed
     */
    function sanitize_sql($sql)
    {
        return preg_replace('/(and\s*|where\s*)$/i', '', $sql);
    }


    /**
     * 清洗表单字段
     *
     * @param array $data
     * @return array
     */
    function sanitize_form(& $data = [])
    {
        if (! is_array($data))
        {
            return $data;
        }

        foreach ($data as $key => $val)
        {
            if (is_array($data[$key]))
            {
                sanitize_form($data[$key]);
            }

            if (is_string($data[$key]))
            {
                $data[$key] = mb_substr($data[$key], 0, 100);
            }

            if (is_int($data[$key]) || (empty($val) && ! is_array($val)) || is_bool($val))
            {
                $data[$key] = intval($val);
            }

            if (is_float($val))
            {
                $data[$key] = floatval($val);
            }
        }

        return $data;
    }

    /**
     * @param $val
     * @return string
     */
    function json_zh($val)
    {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 字符串反转
     * @param $str
     * @return string
     */
    function reverse($str)
    {
        $strlen=strlen($str)-1;
        $temp="";
        for($i=$strlen;$i>=0;$i--)
        {
            $temp.=$str[$i];
        }
        return $temp;
    }


    /**
     * 数字按照千分位表示法分割
     * @param $num
     * @param $separator
     * @return string
     */
    function  thousand_split($num, $separator = ',')
    {
        $str = strrev($num);
        $temp ="";
        for ($i=0; $i < strlen($str); $i++) {
            if($i%3==2 && $str[$i] != $str[strlen($str)-1])
            {
                $temp.=$str[$i] . $separator;
            }
            else
            {
                $temp.=$str[$i];
            }
        }
        return strrev($temp);
    }

    /**
     * 获取两个文件的相对位置
     * @param $dir1
     * @param $dir2
     * @return string
     */
    function absDir($dir1, $dir2)
    {
        $a1=explode("/",trim(dirname($dir1),"/"));
        $b1=explode("/",trim(dirname($dir2),"/"));
        $max=max($a1,$b1);
        for($i=0;$i<count($max);$i++){
            if($a1[$i]==$b1[$i]){
                unset($a1[$i]);
                unset($b1[$i]);
            }
        }
        $str="";
        for($j=0;$j<count($b1);$j++)
        {
            $str.="../";
        }

        return $str.implode($a1, "/");
    }

}