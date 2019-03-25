<?php

class Web
{

    public $a='';

	function set_cookie($key, $value, $expir=0, $path='', $domain='', $is_ssl=false)
	{
		setcookie($key, $value, $expir, $path, $domain, $is_ssl);
	}
	
	function get_cookie($key)
	{
		if(isset($_COOKIE[$key]))
		{
			return $_COOKIE[$key];
		}
		
		return null;
	}
	

	
	function redirect($url)
	{
		header("Location:".$url);
	}


	function build_query($arr)
	{
		return http_build_query($arr);
	}

    function unset_cookie($key)
    {
        setcookie($key, '', 0);
    }

	function  security_cookie($key, $value, $salt)
	{
		setcookie($key, $value.','.md5($value.$salt));
	}
	
	function auth_cookie($key, $salt)
	{
		if(isset($_COOKIE[$key]))
		{
			list($value, $cookie_hash) = explode(',', $_COOKIE[$key]);
			if(md5($value.$salt) == $cookie_hash)
			{
				return 'auth success';
			}
			
			return 'auth failed';
		}
		
		return 'auth failed';
	}
	
	function flush_controll($callback)
	{
		ob_start($callback);  //传入一个回调对缓冲区字符串做处理，php被换成java
		
		echo 'this is php ob modules test';
		
		ob_end_flush();
	}

    function url_rewrite_var($key, $value)
    {
        output_add_rewrite_var($key, $value);
        return '<a href="index.php?age=26">链接</a>';  //index.php?age=26&name=tcl
    }



	function get_php_environment_var($key='')
	{
		//return phpinfo();    // php环境变量列表
        //return getenv($key); // 获取列表环境变量值
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $_SERVER;
	}
	
	function set_php_environment_var($key='', $value='')
	{
		putenv("{$key}={$value}");
	}

	function get_ip()
    {
        // getenv() 使用示例
//        $ip  =  getenv ( 'REMOTE_ADDR' );
        $ip  =  getenv ('SERVER_SIGNATURE');

        // 或简单仅使用全局变量（$_SERVER 或 $_ENV）
//        $ip  =  $_SERVER ['REMOTE_ADDR'];
//        $ip  =  $_SERVER['HTTP_HOST'];

        return $ip;
    }

	function session()
	{
		session_start();
		if(isset($_SESSION['num']))
		{
			return $_SESSION['num'] ++;
		}
		return $_SESSION['num'] = 0;
	}
	
	function prevent_session()
	{

		ini_set('session.use_only_cookies', true);  //只允许用cookie传递session
		session_start();
		if(! isset($_SESSION['tmp']) || $_SESSION['tmp'] > 10)
		{
			session_regenerate_id();
			$_SESSION['tmp'] = 0;
			return $_SESSION['tmp'];
		}
		return ++$_SESSION['tmp'];
	}

	function login_out()
	{
		session_destroy();
	}





}

	$web = new  Web;

//	echo '<pre>';

	//cookie
	//$web->set_cookie('name', 'tcl', 0, '', '127.0.0.1');
	//$web->unset_cookie('name');
	//echo $web->get_cookie('name');

	
	//重定向
	//$web->redirect('http://www.baidu.com');


	//构造查询字符串
	//	echo $web->build_query(['name' => 'tcl', 'phone' => 123, 'hash' => '#']);
	
	
	//cookie加密
	//$web->security_cookie('name', 'tcl', 'asd');
	//echo $web->auth_cookie('name', 'asd');
	
	
	//flush 输出控制
	//	function replace_str($str)
	//	{
	//		return preg_replace('/php/', 'java', $str);
	//	}
	//	$web->flush_controll('replace_str');
	
	
	//获取/设置环境变量
//		$web->set_php_environment_var('name', 'tcl');
//		$env = $web->get_php_environment_var();
//		var_dump($env);
	
	
	//session
	//echo $web->session();

	//防止session劫持, 为<a>标签中的相对url链接添加key=value键值对
	//echo $web->url_rewrite_var('name', 'tcl');
	//防止session定制
	//echo $web->prevent_session();
	
    echo $web->get_ip();

?>