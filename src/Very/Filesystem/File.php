<?php namespace Very\Filesystem;

class File {


    /**
     * 分析目标目录的读写权限
     *
     * @access public
     *
     * @param string $dir_name 目标目录
     * @param int    $mode     权限值
     *
     * @return boolean
     */
    public function mkdir($dir_name, $mode = 0755) {

        if (is_dir($dir_name)) {
            chmod($dir_name, $mode);
            return true;
        }
        return mkdir($dir_name, $mode, true);
    }

    /**
     * 获取目录内文件
     *
     * @param string $dir_name 所要读取内容的目录名
     * @param array  $filter   过滤的文件
     *
     * @return array
     */
    public function readDir($dir_name, $filter = array('.cvs', '.svn', '.git')) {
        if (!is_dir($dir_name)) {
            return false;
        }

        $handle = opendir($dir_name);

        $files = array();
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' || in_array($file, $filter)) {
                continue;
            }
            $files[] = $file;
        }

        closedir($handle);

        return $files;
    }


    /**
     * 获取目录以及子目录内文件
     *
     * @param string $dir_name 所要读取内容的目录名
     * @param array  $filter   过滤的文件
     *
     * @return array
     */
    public function scanDir($dir_name, $filter = array('.cvs', '.svn', '.git')) {
        if (!is_dir($dir_name)) {
            return false;
        }

        $dir_name = rtrim($dir_name, '/') . '/';

        $handle = opendir($dir_name);

        static $files = array();
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' || in_array($file, $filter)) {
                continue;
            }
            if (is_dir($dir_name . $file)) {
                $this->scanDir($dir_name . $file, $filter);
            } else {
                $files[] = $dir_name . $file;
            }
        }

        closedir($handle);

        return $files;
    }

    /**
     * 将一个文件夹内容复制到另一个文件夹
     *
     * @param string $source 被复制的文件夹名
     * @param string $dest   所要复制文件的目标文件夹
     *
     * @return boolean
     */
    public function copy($source, $dest) {

        if (!is_dir($source)) {
            $dest  = $this->mkdir($dest);
            $files = $this->readDir($source);

            foreach ($files as $file) {
                if (is_dir($source . '/' . $file)) {
                    $this->copy($source . '/' . $file, $dest . '/' . $file);
                } else {
                    copy($source . '/' . $file, $dest . '/' . $file);
                }
            }
        } elseif (is_file($source)) {
            return copy($source, $dest);
        }

        return true;
    }


    /**
     * 文件或文件夹重命名或移动文件
     *
     * @access public
     *
     * @param string $source 源文件
     * @param string $new    新文件名或路径
     *
     * @return boolean
     */
    public function move($source, $new) {
        //文件及目录分析
        if (is_file($source)) {
            return rename($source, $new);
        } elseif (is_dir($source)) {
            $files = $this->readDir($source);

            foreach ($files as $file) {
                if (is_dir($source . '/' . $file)) {
                    $this->move($source . '/' . $file, $new . '/' . $file);
                } else {
                    if (copy($source . '/' . $file, $new . '/' . $file)) {
                        unlink($source . '/' . $file);
                    }
                }
            }

            rmdir($source);
        }

        return false;
    }

    /**
     * 删除文件夹或者文件
     *
     * @param string  $source 所要删除文件的路径
     * @param boolean $option 是否删除子目录
     *
     * @return boolean
     */
    public function delete($source, $option = false) {

        if (is_file($source)) {
            return unlink($source);
        } elseif (is_dir($source)) {
            $files = $this->readDir($source);

            foreach ($files as $file) {
                if (is_dir($source . '/' . $file) && $option == true) {
                    $this->delete($source . '/' . $file, $option);
                } elseif (is_file($source . '/' . $file)) {
                    $this->delete($source . '/' . $file, $option);
                }
            }
            rmdir($source);
        }
        return true;
    }

    /**
     * 文件写操作
     *
     * @access public
     *
     * @param string $file_name 文件路径
     * @param string $content   文件内容
     *
     * @return boolean
     */
    public function write($file_name, $content = '') {

        if (!is_file($file_name)) {
            return false;
        }

        return file_put_contents($file_name, $content, LOCK_EX);
    }


    /**
     * 文件写操作
     *
     * @access public
     *
     * @param string $file_name 文件路径
     * @param string $content   文件内容
     *
     * @return boolean
     */
    public function read($file_name, $content = '') {

        if (!is_file($file_name)) {
            return false;
        }

        return file_get_contents($file_name, $content, LOCK_EX);
    }

    /**
     * 字节格式化 把字节数格式为 B K M G T 描述的大小
     *
     * @access public
     *
     * @param integer $bytes 文件大小
     * @param integer $dec   小数点后的位数
     *
     * @return string
     */
    public function formatBytes($bytes, $dec = 2) {

        $unitpow = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $pos     = 0;
        while ($bytes >= 1024) {
            $bytes /= 1024;
            $pos++;
        }

        return round($bytes, $dec) . ' ' . $unitpow[$pos];
    }
}