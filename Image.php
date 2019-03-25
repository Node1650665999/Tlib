<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/3
 * Time: 14:51
 */
namespace Lib;

class Image
{
    /**
     * @var null  原图片路径
     */
    private $source_img = null;

    /**
     * @var null    图片宽度
     */
    private $origin_width = null;


    /**
     * @var null   图片高度
     */
    private $origin_height = null;


    /**
     * @var null  mime类型
     */
    private $mime = null;


    /**
     * @var null  图片类型
     */
    private $extension  = null;


    /**
     * @var null  1 = GIF，2 = JPG，3 = PNG
     */
    private $type   = null;


    /**
     * @var null   源图片所在目录
     */
    private $source_folder = null;


    /**
     * @var null   源图片文件名
     */
    private $img_filename =  null;


    public function __construct($source_img = null)
    {

        if (!file_exists($source_img)) throw new  Exception('请选择图片');

        $this->init($source_img);
    }


    /**
     * 初始化操作
     *
     * @param $source_img
     * @throws Exception
     */
    private  function init($source_img)
    {

        /*
        *  路径解析
        * */
        $x                         = explode('/', $source_img);
        $this->img_filename        = end($x);
        $this->source_folder       = str_replace($this->img_filename, '', $source_img);

        $info = $this->image_info($source_img);
        $this->source_img    = $source_img;
        $this->origin_width  = $info['width'];
        $this->origin_height = $info['height'];
        $this->type          = $info['type'];
        $this->mime          = $info['mime'];
        $this->extension     = $info['extension'];
    }

    /**
     * 图像信息
     *
     * @param $source_img
     * @return array
     * @throws Exception
     */
    public function image_info($source_img)
    {

        if (!file_exists($source_img)) throw new  Exception('图片不存在');

        $info = getimagesize($source_img);
        return [
            'width'  => $info[0],
            'height' => $info[1],
            'mime'   => $info['mime'],
            'size'   => filesize($source_img),
            'type'   => $info[2],
            'extension' => explode('/', $info['mime'])[1],
        ];
    }



    /**
     * 缩放
     *
     * @param $width
     * @param $height
     * @param string $to_dest
     * @return $this
     * @throws Exception
     */
    public function  resize($width, $height, $to_dest='')
    {

        //等比缩放/非等比缩放
        $scale_state = ($width / $this->origin_width) > ($height / $this->origin_height);
        if ($scale_state) {
            $scale = $height / $this->origin_width;
        } else {
            $scale = $width / $this->origin_height;
        }
        $width  = floor($this->origin_width * $scale);
        $height = floor($this->origin_height * $scale);

        if(empty($to_dest))
        {
            $to_dest =  $this->source_img;
        }

        $this->img_oprate(0, 0, $width, $height, $to_dest);

        $this->init($to_dest);
        return  $this;
    }


    /**
     * 裁剪
     *
     * @param $from_x
     * @param $from_y
     * @param $offset_width
     * @param $offset_height
     * @param string $to_dest
     * @return $this
     * @throws Exception
     */
    public function crop($from_x, $from_y, $offset_width, $offset_height, $to_dest='')
    {

        //裁剪时对原图的最大裁剪也就是给定的高度和宽度
        $this->origin_width = $offset_width;
        $this->origin_height= $offset_height;

        if(empty($to_dest))
        {
            $to_dest =  $this->source_img;
        }

        $this->img_oprate($from_x, $from_y, $offset_width, $offset_height, $to_dest);

        $this->init($to_dest);
        return  $this;

    }


    /**
     * 旋转
     *
     * @param $angle
     * @return $this
     * @throws Exception
     */
    public function rotate($angle)
    {

        if(! ($source_handle = $this->create_from_img()))  throw new Exception('旋转失败');
        $dest_handle = imagerotate($source_handle, $angle, 0);
        $to_dest = $this->source_folder.'r_'.$this->img_filename;
        $this->save_img($dest_handle, $to_dest);

        imagedestroy($source_handle);
        imagedestroy($dest_handle);

        $this->init($to_dest);
        return  $this;
    }


    /**
     * 图片水印
     *
     * @param $x
     * @param $y
     * @param $overlay_img
     * @param int $opacity
     * @return $this
     * @throws Exception
     */
    public function water_mark_img($x, $y, $overlay_img, $opacity=100)
    {
        $overlay_info   = $this->image_info($overlay_img);
        $overlay_width  = $overlay_info['width'];
        $overlay_height = $overlay_info['height'];

        $source_handle  = $this->create_from_img();
        $overlay_handle = $this->create_from_img($overlay_img, $overlay_info['type']);


        if($opacity >= 100)
        {
            imagecopy($source_handle,$overlay_handle,$x,$y,0,0,$overlay_width,$overlay_height);
        }
        else
        {
            //如果有需要，可以指定某个像素点的颜色作为透明色
            imagecolortransparent($overlay_handle, imagecolorat($overlay_handle, 20, 20));
            imagecopymerge($source_handle,$overlay_handle,$x,$y,0,0,$overlay_width,$overlay_height,$opacity);
        }

        //如果源图像是png图像，保持其透明度
        if ($this->type === 3)
        {
            imagealphablending($source_handle, false);
            imagesavealpha($source_handle, true);
        }


        $to_dest = $this->source_folder.'wm_'.$this->img_filename;
        $this->save_img($source_handle, $to_dest);

        imagedestroy($source_handle);
        imagedestroy($overlay_handle);


        //支持链式调用
        $this->init($to_dest);
        return  $this;
    }


    /**
     * 文字水印
     *
     * @param $x
     * @param $y
     * @param $font_src
     * @param $font_size
     * @param $text
     * @return $this
     */
    public  function water_mark_text($x,$y,$font_src,$font_size,$text)
    {
        $source_handle  = $this->create_from_img();

        $color = $this->create_color($source_handle)['black'];

        //是否有字体文件
        if($font_src)
        {
            imagettftext($source_handle, $font_size, 0, $x, $y, $color, $font_src, $text);
        }
        else
        {
            imagestring($source_handle, $font_size, $x, $y, $text, $color);
        }


        $to_dest = $this->source_folder.'wt_'.$this->img_filename;
        $this->save_img($source_handle, $to_dest);

        imagedestroy($source_handle);

        $this->init($to_dest);
        return $this;
    }


    /**
     * 创建调色板图像
     *
     * @param null $source_img
     * @param null $type
     * @return bool|resource
     */
    private function create_from_img($source_img=null, $type=null)
    {

        if($source_img) $this->source_img  = $source_img;
        if($type)       $this->type        = $type;

        switch($this->type)
        {
            case 1:
                return  imagecreatefromgif($this->source_img);
            case 2:
                return  imagecreatefromjpeg($this->source_img);
            case 3:
                return imagecreatefrompng($this->source_img);
            default:
                return  false;
        }
    }


    /**
     * 创建真色彩图像
     *
     * @param $width
     * @param $height
     * @return resource
     */
    private function create_from_true_color($width, $height)
    {
        return imagecreatetruecolor($width, $height);
    }


    /**
     * 生成颜色
     *
     * @param $source_handle
     * @return mixed
     */
    private function  create_color($source_handle)
    {
        $color_arr['black']=imagecolorallocate($source_handle, 0,0,0);  //颜色必须是RGB代码
        $coloe_arr['white']=imagecolorallocate($source_handle, 255,255,255);

        $coloe_arr['color_random']=imagecolorallocate($source_handle, rand(0, 120), rand(0, 120), rand(0, 120));

        return $color_arr;
    }


    /**
     *
     * 裁剪和缩放的工具函数
     *
     * @param int $from_x
     * @param int $from_y
     * @param $width
     * @param $height
     * @param $to_dest
     * @throws Exception
     */
    private function img_oprate($from_x=0, $from_y=0, $width, $height, $to_dest)
    {

        //生成原图片和目标图片资源句柄
        $creat_handle_ok = ! ($source_handle=$this->create_from_img()) ||
            ! ($dest_handle=$this->create_from_true_color($width, $height));

        if($creat_handle_ok)  throw new Exception('创建图像失败');


        $copy_ok = imagecopyresampled ( $dest_handle , $source_handle, 0, 0,$from_x, $from_y, $width, $height, $this->origin_width ,  $this->origin_height );
        if($copy_ok === false) throw new Exception('copy图像失败');

        $save    = $this->save_img($dest_handle, $to_dest);
        if($save === false)  throw new Exception('图像保存失败');


        imagedestroy($source_handle);
        imagedestroy($dest_handle);
    }


    /**
     * 保存图像到文件
     * @param $dest_handle
     * @param $file_name
     * @return bool
     */
    private function save_img($dest_handle, $file_name)
    {

        switch($this->type)
        {
            case 1:
                return imagegif($dest_handle, $file_name);
            case 2:
                return imagejpeg($dest_handle, $file_name);
            case 3:
                return imagepng($dest_handle, $file_name);
            default:
                return  false;
        }
    }

    /**
     * 向浏览器输出图像
     * @param $source_handle
     *
     */
    public function display_img($source_handle)
    {
        header('Content-Disposition: filename='.$this->source_img.';');
        header('Content-Type: '.$this->mime);
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

        switch ($this->type)
        {
            case 1	:	imagegif($source_handle);
                break;
            case 2	:	imagejpeg($source_handle);
                break;
            case 3	:	imagepng($source_handle);
                break;
            default:	echo 'Unable to display the image';
                break;
        }
    }

    //图片类型转换


    //图片缓存


    //url作图
}

