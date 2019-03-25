<?php
namespace Lib;
class Benchmark
{
	/**
	 * @var array  时间节点列表
     */
	private $time_point = [];


	/**
	 * 某个函数的执行时间
	 *
	 * @param $callback, $callback 可使用 call_user_func_array 方式传入
	 * @param $args
	 * @return string
     */
	function func_excute_time($callback, $args = null)
	{
		
		$this->mark('star');

		$callback($args);

		return $this->consume_time('start');
	}
	

	/**
	 * 记录时间节点
	 *
	 * @param $point
     */
	function mark($point)
	{
		$this->time_point[$point] = microtime(true);
	}


	/**
	 * 计算时间差
	 *
	 * @param string $point1
	 * @param string $point2
	 * @return string
     */
	function consume_time($point1 = '', $point2 = '')
	{
		if(! isset($this->time_point[$point1]))
		{
			return '';
		}

		if(! isset($this->time_point[$point2]))
		{
			$this->time_point[$point2] = microtime(true);
		}

		return number_format($this->time_point[$point2] - $this->time_point[$point1], 4);
	}


    /**
     * 获取内存的使用情况
     *
     * @return string
     */
    function memory_usage()
    {
       return round(memory_get_usage() / 1024 / 1024, 2).'MB';
    }

}


	

	

?>