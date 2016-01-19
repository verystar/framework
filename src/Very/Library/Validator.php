<?php namespace Very\Library;
/**
 * 验证操作库
 * @author caixudong
 */
class Validator {

    static public function getInstance() {
        static $_instance = null;
        return $_instance ?: $_instance = new self;
    }

    /**
     *    数据基础验证-检测字符串长度
     *
     * @param  string $value 需要验证的值
     * @param  int    $min   字符串最小长度
     * @param  int    $max   字符串最大长度
     *
     * @return bool
     */
    public function isLength($value, $min = 0, $max = 0) {
        $value = trim($value);
        if ($min != 0 && strlen($value) < $min)
            return false;
        if ($max != 0 && strlen($value) > $max)
            return false;
        return true;
    }

    /**
     *    数据基础验证-是否必须填写的参数
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isRequire($value) {
        return preg_match('/.+/', trim($value));
    }

    /**
     *    数据基础验证-是否是空字符串
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isEmpty($value) {
        if (empty($value) || $value == "")
            return false;
        return true;
    }

    /**
     *    数据基础验证-检测数组，数组为空时候也返回FALSH
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isArray($value) {
        if (!is_array($value) || empty($value))
            return false;
        return true;
    }

    /**
     *    数据基础验证-是否是Email 验证：xxx@qq.com
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isEmail($value) {
        return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', trim($value));
    }

    /**
     *    数据基础验证-是否是IP
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isIP($value) {
        return preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])$/', trim($value));
    }

    /**
     *    数据基础验证-是否是数字类型
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isNumber($value) {
        return is_numeric(trim($value));
    }

    /**
     *    数据基础验证-是否是身份证
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isCard($value) {
        return preg_match("/^(\d{15}|\d{17}[\dx])$/i", $value);
    }

    /**
     *    数据基础验证-是否是电话 验证：0571-xxxxxxxx
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isMobile($value) {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', trim($value));
    }

    /**
     *    数据基础验证-是否是移动电话 验证：1385810XXXX
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isPhone($value) {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(13|15)\d{9}$/', trim($value));
    }

    /**
     *    数据基础验证-是否是URL 验证：http://www.easyphp.cc
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isUrl($value) {
        return preg_match('/^https?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/', trim($value));
    }

    /**
     *    数据基础验证-是否是邮政编码 验证：311100
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isZip($value) {
        return preg_match('/^[1-9]\d{5}$/', trim($value));
    }

    /**
     *    数据基础验证-是否是QQ
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isQQ($value) {
        return preg_match('/^[1-9]\d{4,12}$/', trim($value));
    }

    /**
     *    数据基础验证-是否是英文字母
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isEnglish($value) {
        return preg_match('/^[A-Za-z]+$/', trim($value));
    }

    /**
     *    数据基础验证-是否是中文
     *
     * @param  string $value 需要验证的值
     *
     * @return bool
     */
    public function isChinese($value) {
        return preg_match("/^([\xE4-\xE9][\x80-\xBF][\x80-\xBF])+$/", trim($value));
    }

    /**
     * 检查对象中是否有可调用函数
     *
     * @param string $object
     * @param string $method
     *
     * @return bool
     */
    public function isMethod($object, $method) {
        $method = strtolower($method);
        return method_exists($object, $method) && is_callable(array($object, $method));
    }

    /**
     * 检查是否是安全的账号
     *
     * @param string $value
     *
     * @return bool
     */
    public function isSafeAccount($value) {
        return preg_match("/^[a-zA-Z]{1}[a-zA-Z0-9_\.]{3,31}$/", $value);
    }

    /**
     * 检查是否是安全的昵称
     *
     * @param string $value
     *
     * @return bool
     */
    public function isSafeNickname($value) {
        return preg_match("/^[-\x{4e00}-\x{9fa5}a-zA-Z0-9_\.]{2,10}$/u", $value);
    }

    /**
     * 检查是否是安全的用户名
     *
     * @param string $value
     *
     * @return bool
     */
    public function isSafeUsername($value) {
        return preg_match("/^[-\x{4e00}-\x{9fa5}a-zA-Z0-9_]{2,50}$/u", $value);
    }

    /**
     * 检查是否是安全的密码
     *
     * @param string $str
     *
     * @return bool
     */
    public function isSafePassword($str) {
        if (preg_match('/[\x80-\xff]./', $str) || preg_match('/\'|"|\"/', $str) || strlen($str) < 6 || strlen($str) > 20) {
            return false;
        }
        return true;
    }

}
