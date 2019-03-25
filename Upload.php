<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/25
 * Time: 10:30
 */
namespace Lib;
class Upload
{
    /**
     * @var string 允许上传的mime类型,*代表不做限制
     */
    private $allow_type = '*';

    /**
     * @var string  文件mime类型
     */
    private $mime_type = '';

    /**
     * @var string  文件扩展名
     */
    private $ext = '';

    /**
     * @var string 上传的文件 == $_FILES
     */
    private $file = '';

    /**
     * @var bool   是否生成随机文件名
     */
    private $encrypt_name = false;

    /**
     * @var int    文件名长度限制
     */
    private $max_file_len = 0;

    /**
     * @var string  上传文件的保存路径
     */
    private $upload_path = '';

    /**
     * @var string  上传文件的文件名
     */
    private $filename = '';

    /**
     * @var string  上传后文件的命名，如果设置将重命名上传文件名
     */
    private $new_filename = '';

    /**
     * @var string  上传文件在服务器的临时地址
     */
    private $file_temp = '';

    /**
     * @var int  上传文件的错误码
     */
    private $error = 0;

    /**
     * @var bool    是否覆盖同名文件
     */
    private $overwrite = false;

    /**
     * @var int     上传文件允许的最大尺寸
     */
    private $max_size = 0;

    /**
     * @var int 文件大小
     */
    private $file_size = 0;

    /**
     * @var int 图片允许的最大宽度
     */
    public $max_width = 0;

    /**
     * @var int 图片允许的最大高度
     */
    public $max_height = 0;

    /**
     * @var int 图片允许的的最小宽度
     */
    public $min_width = 0;

    /**
     * @var    int 图片的允许的最小高度
     */
    public $min_height = 0;

    /**
     * @var int 图片宽度
     */
    private $image_width = 0;

    /**
     * @var int 图片高度
     */
    private $image_height = 0;

    /**
     * @var int  图片类型
     */
    private $image_type = 0;


    /**
     * @var array
     */
    private $error_msg  = [];

    function __construct($config=[])
    {
        $this->initialize($config);
    }

    /**
     * 初始化
     *
     * @param array $config
     */
    private function initialize($config = [])
    {
        if (count($config) > 0) {
            foreach ($config as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        }

    }

    /**
     * 上传文件
     *
     * @return bool
     */
    public  function do_upload()
    {
        if(empty($this->file))
        {
            $this->set_error('请上传文件');
            return false;
        }
        if(! $this->validate_upload_path())
        {
            return false;
        }


        $this->file_temp = $this->file['tmp_name'];
        $this->mime_type = $this->get_file_mime($this->file_temp);
        $this->file_size = $this->file['size'];
        $this->error     = $this->file['error'];
        $this->filename  = $this->file['name'];
        $this->filename  = $this->filename_handle($this->filename);
        $this->ext       = $this->get_extension($this->filename);

        //文件是否能上传
        if(! is_uploaded_file($this->file_temp))
        {
            switch ($this->error)
            {
                case 1:
                    $this->set_error('文件大小超出系统规定的大小');
                    break;
                case 2:
                    $this->set_error('文件大小超出表单规定的大小');
                    break;
                case 3:
                    $this->set_error('文件上传不完整');
                    break;
                case 4:
                    $this->set_error('请上传文件');
                    break;
                case 6:
                    $this->set_error('临时文件找不到');
                    break;
                case 7:
                    $this->set_error('向上传目录写入文件失败');
                    break;
                case 8:
                    $this->set_error('不被允许的扩展名');
                    break;
                default:
                    $this->set_error('请上传文件');
                    break;
            }

            return false;
        }

        //文件大小是否被允许
        if(! $this->is_allow_filesize())
        {
            return false;

        }

        //文件类型是否被允许
        if(! $this->is_allow_type())
        {
            return false;
        }

        //新文件名的处理
        if($this->new_filename)
        {
            if (! $this->validate_new_filename($this->new_filename))
            {
                return false;
            }

            $this->ext      = $this->get_extension($this->new_filename);
            $this->filename = substr($this->new_filename, 0, strrpos($this->new_filename, '.'));
            if(! $this->is_allow_type())
            {
                return false;
            }
        }

        //如果是image，验证大小并记录图像信息
        if($this->is_image())
        {
            if(! $this->validate_imagesize())
            {
                return false;
            }

            $this->set_image_properties();
        }

        //文件名的最大长度
        if($this->max_file_len > 0)
        {
            if($this->filename > $this->max_file_len)
            {
                $this->filename = substr($this->filename, -$this->max_file_len);
            }
        }

        //不覆盖已存在的文件，根据规则生成文件名
        if ($this->overwrite === FALSE)
        {
            if($this->encrypt_name === true)
            {
                $this->filename  = md5(uniqid(mt_rand()));
            }
            if(file_exists($this->upload_path . $this->filename))
            {
                $this->set_error('请重试');
                return false;
            }
        }


        /*
         * 将文件移到目标位置
         * copy做兼容性处理，如果不支持copy,
         * 就用move_upload_file
         *
         * */

        if ( ! @copy($this->file_temp, $this->upload_path.$this->filename.'.'.$this->ext))
        {
            if ( ! @move_uploaded_file($this->file_temp, $this->upload_path.$this->filename.'.'.$this->ext))
            {
                $this->set_error('upload_destination_error');
                return FALSE;
            }
        }
        return true;

    }

    /**
     * 文件保存后的信息
     *
     * @param null $index
     * @return array|null
     */
    public function file_info($index = null)
    {
        $data =
        [
            'filename'       =>  $this->filename,
            'file_type'      =>  $this->ext,
            'file_location'  =>  $this->upload_path.$this->filename.'.'.$this->ext,
            'mime_type'      =>  $this->mime_type,
            'file_size'      =>  $this->file_size,
            'image_width'    =>  $this->image_width,
            'image_height'   =>  $this->image_height,
        ];
       if(! empty($index))
       {
           return isset($data[$index]) ? $data[$index] : NULL;

       }
        return $data;
    }

    /**
     * 验证上传目录
     *
     * @return bool
     */
    public function validate_upload_path()
    {
        if ($this->upload_path === '')
        {
            $this->set_error('上传目录不能为空');
            return FALSE;
        }


        if (realpath($this->upload_path) !== FALSE)
        {
            $this->upload_path = str_replace('\\', '/', realpath($this->upload_path));
        }

        if ( ! is_dir($this->upload_path))
        {
            $this->set_error('上传目录不存在');
            return FALSE;
        }

        if ( ! is_writeable($this->upload_path))
        {
            $this->set_error('在上传目录无法写入文件');
            return FALSE;
        }

        $this->upload_path = preg_replace('/(.+?)\/*$/', '\\1/',  $this->upload_path);
        return TRUE;
    }


    /**
     * 获取文件的mime
     *
     * @param $file_temp
     * @return string
     */
    private function get_file_mime($file_temp)
    {
        $regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';
        $finfo = @finfo_open(FILEINFO_MIME);
        if (is_resource($finfo))
        {
            $mime = @finfo_file($finfo, $file_temp);
            finfo_close($finfo);

            if (is_string($mime) && preg_match($regexp, $mime, $matches))
            {
                return   $matches[1];
            }
        }

        return '';

    }

    /**
     * 处理文件名
     *
     * @param $file_name
     * @return string
     */
    private function filename_handle($file_name)
    {

        if(false === ($ext_pos = strrpos($file_name, '.')))
        {
            return $file_name;
        }

        $ext         = substr($file_name, $ext_pos);
        $file_name   = substr($file_name, 0, $ext_pos);
        $file_name   = str_replace('.', '_', $file_name).$ext;

        return $file_name;
    }

    /**
     * 验证新文件名
     *
     * @param $new_filename
     * @return bool
     */
    private  function validate_new_filename($new_filename)
    {
        $ext = $this->get_extension($new_filename);
        if(! $this->is_image() && $ext != $this->ext)
        {
            $this->set_error('新文件类型和旧文件类型不一致');
            return false;
        }


        if($this->is_image() && ! in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'jpe']))
        {
            $this->set_error('不能将图片类型命名为别的文件类型');
            return false;
        }

        if($this->allow_type != '*'   && ! in_array($ext, $this->allow_type))
        {
            $this->set_error('新文件名类型不被支持');
            return false;
        }

        return true;
    }

    /**
     * 获取上传文件的扩展名
     *
     * @param $filename
     * @return mixed|string
     */
    private function get_extension($filename)
    {
        $file_split  = explode('.', $filename);
        if(count($file_split) === 1)
        {
            return'';
        }
       return end($file_split);
    }

    /**
     * 验证上传文件的类型
     *
     * @return bool
     */
    private function  is_allow_type()
    {
        if($this->allow_type == '*')
        {
            return true;
        }

        if($this->allow_type !== '*' && ! is_array($this->allow_type))
        {
            $this->set_error('请设置允许上传的文件类型');
            return false;
        }

        $ext = strtolower($this->ext);

        if(! in_array($ext, $this->allow_type))
        {
            $this->set_error('文件类型不符合要求');
            return false;
        }

        if(in_array($ext, ['jpg', 'png', 'jpeg', 'gif', 'jpe']) && @getimagesize($this->file_temp) === false)
        {
            return false;
        }


        return true;
    }

    /**
     * 验证上传文件的尺寸
     *
     * @return bool
     */
    private function is_allow_filesize()
    {
        if($this->file_size > $this->max_size && $this->max_size !== 0)
        {
            return false;
        }

        return true;
    }


    /**
     * 验证上传图片的尺寸
     *
     * @return bool
     */
    private function validate_imagesize()
    {

        $img_info = @getimagesize($this->file_temp);

        if ($this->max_width > 0 && $img_info[0] > $this->max_width)
        {
            return FALSE;
        }

        if ($this->max_height > 0 && $img_info[1] > $this->max_height)
        {
            return FALSE;
        }

        if ($this->min_width > 0 && $img_info[0] < $this->min_width)
        {
            return FALSE;
        }

        if ($this->min_height > 0 && $img_info[1] < $this->min_height)
        {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * 上传文件是否为图片
     *
     * @return bool
     */
    private function is_image()
    {
        $png_mimes  = array('image/x-png');
        $jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');

        if (in_array($this->mime_type, $png_mimes))
        {
            $this->mime_type = 'image/png';
        }
        elseif (in_array($this->mime_type, $jpeg_mimes))
        {
            $this->mime_type = 'image/jpeg';
        }

        $img_mimes = array('image/gif',	'image/jpeg', 'image/png');

        return in_array($this->mime_type, $img_mimes, TRUE);
    }

    /**
     * 记录图片信息
     */
    private function set_image_properties()
    {
        if (false !== ($img_info = @getimagesize($this->file_temp)))
        {
            $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

            $this->image_width	= $img_info[0];
            $this->image_height	= $img_info[1];
            $this->image_type	= isset($types[$img_info[2]]) ? $types[$img_info[2]] : 'unknown';
        }
    }

    /**
     * 记录错误
     *
     * @param $msg
     * @return $this
     */
    private function set_error($msg)
    {
        $this->error_msg[] = $msg;
        return $this;
    }

    /**
     * 显示错误
     * @param string $open
     * @param string $close
     * @return string
     */
    public function display_error($open = '<p>', $close = '</p>')
    {
        return (count($this->error_msg) > 0) ? $open.implode($close.$open, $this->error_msg).$close : '';
    }


}