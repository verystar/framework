<?php

namespace Very\Filesystem;

class Upload
{
    //文件保存目录路径
    public $configs;

    public function init($configs)
    {
        $default_configs = array(
            'directory' => '',
            'allowed' => array('jpg', 'gif', 'png', 'jpeg'), //文件后缀
            'max_size' => '3M', //文件最大大小
            'file_name' => '', //自定义文件名
        );

        $this->configs = array_merge($default_configs, $configs);

        if (!is_dir($this->configs['directory'])) {
            $this->mkdirs($this->configs['directory'], 0775);
        }

        if (!$this->checkDir($this->configs['directory'])) {
            return array(
                'code' => 400,
                'msg' => '目录'.$this->configs['directory'].'创建失败',
            );
        }

        return $this;
    }

    public function valid(array $file)
    {
        return isset($file['name']) && isset($file['tmp_name']) && isset($file['size']) && isset($file['type']);
    }

    //验证目录
    public function checkDir($directory)
    {
        if (is_dir(realpath($directory)) && is_writable($directory)) {
            return true;
        }

        return false;
    }

    //验证类型
    public static function checkType(array $file, array $allowed)
    {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        return in_array($file_ext, $allowed);
    }

    public static function checkSize(array $file, $allowed_size)
    {
        $allowed_size = strtoupper($allowed_size);

        if (!preg_match('#[0-9]++[GMKB]#i', $allowed_size)) {
            return false;
        }

        switch (substr($allowed_size, -1)) {
            case 'G':
                $allowed_size = intval($allowed_size) * pow(1024, 3);
                break;
            case 'M':
                $allowed_size = intval($allowed_size) * pow(1024, 2);
                break;
            case 'K':
                $allowed_size = intval($allowed_size) * pow(1024, 1);
                break;
            case 'B':
                $allowed_size = intval($allowed_size);
                break;
        }

        return $file['size'] <= $allowed_size;
    }

    public function mkdirs($dir, $mode = 0755)
    {
        return mkdir($dir, $mode, true);
    }

    public function save(array $file)
    {
        $configs = $this->configs;
        //有上传文件时
        if ($this->valid($file) === true) {

            //检测扩展名
            if ($this->checkType($file, $configs['allowed']) === false) {
                return array(
                    'code' => 301,
                    'msg' => '不允许上传该类型的文件，允许的文件有：'.implode(',', $configs['allowed']),
                );
            }

            //检测大小
            if ($this->checkSize($file, $configs['max_size']) === false) {
                return array(
                    'code' => 302,
                    'msg' => '文件超过了最大'.$configs['max_size'],
                );
            }

            if (@is_uploaded_file($file['tmp_name']) === false) {
                return array(
                    'code' => 401,
                    'msg' => '上传临时文件不存在',
                );
            }
            $file_path = $this->configs['directory'].DIRECTORY_SEPARATOR;

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($configs['file_name']) {
                $file_name = $configs['file_name'].'.'.$file_ext;
            } else {
                $file_name = md5(uniqid().time()).'.'.$file_ext;
            }

            //存储文件
            if (move_uploaded_file($file['tmp_name'], $file_path.$file_name) === false) {
                return array(
                    'code' => 403,
                    'msg' => '存储'.$file_name.'失败',
                );
            }

            return array(
                'code' => 200,
                'file' => $file_path.$file_name,
                'file_name' => $file_name,
                'msg' => '上传成功',
                'ori_name' => $file['name'],
            );
        } else {
            return array(
                'code' => 404,
                'msg' => '没有上传文件',
            );
        }
    }
}
