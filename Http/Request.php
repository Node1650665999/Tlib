<?php
/**
 * Created by TCL
 * User: Administrator
 * Date: 2018/8/16
 * Time: 18:12
 */
namespace Lib\http;
use Lib\Help;
use Lib\Traits\Common;

/**
 * Class Request
 * @package Lib
 */
class Request
{
    use Common;

    /**
     * @var null $_SERVER
     */
    protected $server = null;

    /**
     * Request constructor.
     */
    function __construct()
    {
        $this->server = $_SERVER;
    }

    /**
     * @return string
     */
    public function method()
    {
        return strtoupper($this->server['REQUEST_METHOD']);
    }

    /**
     * @return mixed
     */
    public function scheme()
    {
        return $this->server['REQUEST_SCHEME'];
    }

    /**
     * @return mixed
     */
    public function host()
    {
        return $this->server['HTTP_HOST'];
    }

    /**
     * @return string
     */
    public function root()
    {
        return $this->scheme() . '://' . $this->host();
    }

    /**
     * @return string
     */
    public function path()
    {
        return trim(preg_replace('/\?.*/', '', $this->server['REQUEST_URI']), '/');
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->host() . '/' . $this->path();
    }

    /**
     * @return mixed
     */
    protected function queryString()
    {
        return $this->server['QUERY_STRING'];
    }

    /**
     * @return string
     */
    public function fullUrl()
    {
        return $this->queryString() ? $this->url() . '?' . $this->queryString() : $this->url();
    }

    /**
     * @return string
     */
    public function decodedPath()
    {
        return rawurldecode($this->path());
    }

    /**
     * @param $index
     * @param null $default
     * @return array|mixed|null
     */
    public function segment($index, $default = null)
    {
        $segments = $this->segments();

        if(! $index)
        {
            return $segments;
        }

        return isset($segments[$index-1]) ? $segments[$index-1] : $default;
    }

    /**
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->decodedPath());

        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }

    /**
     * @param null $filed
     * @return null
     */
    public function post($filed = null)
    {
        if(! $filed)
        {
            return $_POST;
        }
        return $_POST[$filed] ?? null;
    }

    /**
     * @param null $filed
     * @return null
     */
    public function get($filed = null)
    {
        if(! $filed)
        {
            return $_GET;
        }
        return $_GET[$filed] ?? null;
    }

    /**
     *
     * @param null $field
     * @return mixed|null
     */
    public function input($field = null)
    {
        if($data = $this->input_json($field))
        {
            return $data;
        }

        if($data = $this->post($field))
        {
            return $data;
        }

        if($data = $this->get($field))
        {
            return $data;
        }

        return null;
    }

    /**
     * json 数据
     * @param null $field
     * @return mixed|null
     */
    protected function input_json($field = null)
    {
        $stream = $this->stream();

        if(! $this->isJson($stream))
        {
            return null;
        }

        $data = \json_decode($stream, true);

        if(empty($field))
        {
            return $data;
        }

        return $data[$field] ?? null;
    }

    /**
     * 输入流
     * @return bool|string
     */
    public function stream()
    {
        return file_get_contents('php://input');
    }


    /**
     * @param $url
     * @param $param
     * @param bool $usecert 是否使用证书
     * @param array $ssl    证书
     * @return bool|mixed
     */
    public function do_post($url, $param, $usecert = false, $ssl=[])
    {
        $curl = curl_init();
        if(stripos($url,"https://") !== false)
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);

        if ($usecert == true)
        {
            //设置证书
            curl_setopt($curl,CURLOPT_SSLCERT, $ssl['sslcert_path']);
            curl_setopt($curl,CURLOPT_SSLKEY, $ssl['sslkey_path']);
        }

        $data               = curl_exec($curl);
        $info               = curl_getinfo($curl);

        curl_close($curl);

        return $this->sanitize_response($data, $info);
    }

    /**
     * get 请求
     *
     * @param $url
     * @return array
     */
    public function do_get($url)
    {
        $curl = curl_init();
        if (stripos($url, "https") !== false)
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $data               = curl_exec($curl);
        $info               = curl_getinfo($curl);

        curl_close($curl);

        return $this->sanitize_response($data, $info);
    }

    /**
     * 处理 http response
     * @param $data
     * @param $info
     * @return bool|mixed
     */
    protected function sanitize_response($data, $info)
    {
        if($info['http_code'] !== 200)
        {
            // todo
            $msg     = "http_code={$info['http_code']}导致请求失败!";
            return false;
        }

        if($this->isJson($data))
        {
            $data = json_decode($data, true);
        }
        return $data;
    }





}