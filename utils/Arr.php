<?php
/**
 * Created by TCL
 * User: Administrator
 * Date: 2019/3/13
 * Time: 11:19
 */
namespace Lib\Utils;
class Arr
{

    /**
     * 数组转换
     * 一维 => 二维
     * 二维 => 一维
     *
     * @param null $data
     * @return array
     */
    public function arrayConversion($data = null)
    {
        $arr = [];
        foreach($data as $akey => $avalue)
        {
            foreach($avalue as $bkey =>$bvalue)
            {
                $arr[$bkey][$akey]= $bvalue;
            }
        }
        return $arr;
    }

    /**
     * 判断数组是一维还是二维
     *
     * @param null $arr
     * @return bool
     */
    public function isDimensionArray($arr = null)
    {
        return count($arr) != count($arr, 1);
    }

    /**
     * 二维数组去重
     * @desc 由于array_unique的元素只能是字符类型，无法处理元素是数组的情况
     * @param $array
     * @return array
     */
    function arrayUnique($array)
    {
        if(empty($array))
        {
            return [];
        }
        foreach ($array as $k => $v)
        {
            foreach ($array as $k2 => $v2)
            {
                if (($v2 == $v) && ($k != $k2))
                {
                    unset($array[$k]);
                }
            }
        }
        return array_values($array);
    }

    /**
     * @param $array
     * @return mixed
     */
    public function shuffle($array)
    {
        shuffle($array);

        return $array;
    }

}