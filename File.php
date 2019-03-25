<?php
namespace Lib;
/**
 * Class File
 */
class File
{
	/**
	 * @var null 压缩对象
     */
	private static $zip = null;

	/**
	 * @var int  目录大小
     */
	private static $total_size = 0;

	/**
	 * 文件拷贝
	 * @param $path
	 * @param $target
	 * @return bool
     */
	function file_copy($path, $target)
	{
		return copy($path, $target);
	}

	/**
	 * 目录拷贝
	 * @param $dir
	 * @param $target
	 * @return bool
     */
	function dir_copy($dir, $target)
	{
		if(! is_dir($dir))
		{
			return  false;
		}

		if(! file_exists($target))
		{
			mkdir($target);
		}

		$handle=opendir($dir);

		while ($filename=readdir($handle))
		{
			if($filename != "." && $filename != "..")
			{
				$sub_file		= $dir.'/'.$filename;
				$target_file	= $target.'/'.$filename;

				if(is_dir($sub_file))
				{
					$func = __FUNCTION__;
					if(! $this->$func($sub_file, $target_file))
					{
						return  false;
					}
				}
				else
				{
					if(! copy($sub_file, $target_file))
					{
						return false;
					}
				}
			}
		}
		//关闭文件资源
		closedir($handle);

		return true;
	}

	/**
	 * 获取目录大小
	 * @param $dir
	 * @return bool|number
     */
	function dir_size($dir)
	{
		$this->dir_iterate($dir, 'count');
		return $this->transByte(self::$total_size);
	}

	/**
	 * 文件/目录剪切
	 * @param $path
	 * @param $target
	 * @return bool
     */
	function cut($path, $target)
	{
		return rename($path, $target);
	}

	/**
	 * 删除文件
	 * @param $paths
	 * @return bool
     */
	function file_delete($paths)
	{
		$paths   = is_array($paths) ? $paths : func_get_args();
		$success = true;

		foreach ($paths as $path)
		{
			try
            {
				if (! @unlink($path))
				{
					$success = false;
				}
			}
			catch (Exception $e)
            {
				$success = false;
			}
		}
		return $success;
	}


	/**
	 * 删除目录
	 *
	 * @param $dir
	 * @return bool
     */
	function dir_delete($dir)
	{
		$this->dir_iterate($dir, 'delete');
		try
        {
			if(@rmdir($dir))
				return true;
			else
				return false;
		}
		catch (Exception $e)
        {
			return false;
		}
	}

    /**
     * 目录遍历
     * @param $dir
     * @param null $operate_type
     * @return bool
     */
    private function dir_iterate($dir, $operate_type=null)
	{
		if(! is_dir($dir))
		{
			return  false;
		}
		$handle=opendir($dir);

		//遍历目录
		while ($filename=readdir($handle))
		{
			//读取目录下的文件,过滤掉含有的.和..目录
			if($filename != "." && $filename != "..")
			{
				//将目录下的文件和当前目录连接
				$sub_file=$dir."/".$filename;

				//如果是目录则继续遍历，如果是文件则拷贝
				if(is_dir($sub_file) === TRUE)
				{
					//再次遍历
					$func = __FUNCTION__;
					$this->$func($sub_file);
				}
				else
				{
					switch ($operate_type)
                    {
						case 'delete':
							@unlink($sub_file);
							break;

						case 'count':
							self::$total_size += filesize($sub_file);
							break;

						case 'tar':
							self::$zip->addFile($sub_file);
							break;

						default:
							break;
					}

				}

			}
		}
		//关闭文件资源
		closedir($handle);
	}

	/**
	 * 当前目录的子文件
	 *
	 * @param $dir
	 * @return array
     */
	function files($dir)
	{
		$glob = glob($dir.'/*');

		if ($glob === false)
		{
			return [];
		}
		return array_filter($glob, function ($file)
        {
			return filetype($file) == 'file';
		});
	}

	/**
	 * 转换字节大小
	 * @param number $size
	 * @return number
	 */
	private function transByte($size) {
		$arr = array ("B", "KB", "MB", "GB", "TB", "EB" );
		$i = 0;
		while ( $size >= 1024 ) {
			$size /= 1024;
			$i ++;
		}
		return round ( $size, 2 ) . $arr [$i];
	}

	/**
	 * 压缩文件/目录
	 *
	 *  $zip->open这个方法第一个参数表示处理的zip文件名。
	 * 	第二个参数表示处理模式，ZipArchive::OVERWRITE表示如果zip文件存在，就覆盖掉原来的zip文件。
	 *	如果参数使用ZIPARCHIVE::CREATE，系统就会往原来的zip文件里添加内容。
	 *	如果不是为了多次添加内容到zip文件，建议使用ZipArchive::OVERWRITE。
	 *	使用这两个参数，如果zip文件不存在，系统都会自动新建。
	 *	如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
	 *
	 * @param $file_zip
	 * @param string $path
	 * @param int $flag   1增量压缩/2覆盖压缩
	 * @return bool
     */
	function compress($file_zip, $path = '', $flag=1)
	{
		self::$zip = new  ZipArchive;
		$archive_type = $flag === 1 ? ZipArchive::CREATE : ZipArchive::OVERWRITE;

		if (self::$zip->open($file_zip, $archive_type))
		{
			if(is_dir($path))
			{
				$this->dir_iterate($path, 'tar');
			}
			else
			{
				self::$zip->addFile($path);
			}

			self::$zip->close();

			return true;
		}

		return  false;
		
	}

    /**
     * 解压缩
     * @param $file_zip
     * @param $folder
     * @return bool
     */
    function uncompress($file_zip, $folder)
	{
		$zip = new  ZipArchive;
		if ($zip->open($file_zip) === TRUE)
		{
			$zip->extractTo($folder);
			$zip->close();

			return true;
		}
		return false;
	}

	/**
	 * 文件下载
	 *
	 * @param $filename
	 * @param int $is_download
	 * @return string
     */
	function download_file($filename, $is_download = 1)
	{
		if(! file_exists($filename))
		{
			return '文件不存在';
		}

		//下载
		if($is_download)
		{
			//当成附件浏览器才能允许下载
			header('Content-Type: application/octet-stream');  //文件类型二进制流
			header('Content-Disposition: attachment; filename='.basename($filename));
		    header('Content-Length: ' . filesize($filename));
	        readfile($filename);
		    exit;
		}
		
		//预览
		header('Location:' . $filename);
	}
	
}
//	$file = new  FileTool();
//	文件下载
//	$file->download_file('a.pdf', 1);