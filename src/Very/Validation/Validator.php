<?php

namespace Very\Validation;

use Countable;
use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use Very\Support\Traits\Macroable;
use Very\Support\Str;
use Very\Support\Arr;
use InvalidArgumentException;

/**
 * 验证操作库.
 *
 * @author caixudong
 */
class Validator
{
    use Macroable;

    //form data
    protected $data;

    protected $replaceRules = [
        'after'                => [
            ':date'
        ],
        'before'               => [
            ':date'
        ],
        'between'              => [
            ':min',
            ':max'
        ],
        'different'            => [
            ':other'
        ],
        'digits_between'       => [
            ':min',
            ':max'
        ],
        'max'                  => [
            ':max'
        ],
        'min'                  => [
            ':min'
        ],
        'required_if'          => [
            ':other',
            ':value'
        ],
        'required_unless'      => [
            ':other',
            ':value'
        ],
        'required_with'        => [
            ':values'
        ],
        'required_with_all'    => [
            ':values'
        ],
        'required_without'     => [
            ':values'
        ],
        'required_without_all' => [
            ':values'
        ],
    ];

    /**
     * Parse the human-friendly rules into a full rules array for the validator.
     *
     * @param  array $rules
     *
     * @return array
     */
    protected function explode($rules)
    {
        foreach ($rules as $key => $rule) {
            $rules[$key] = explode('|', $rule);
        }

        return $rules;
    }

    /**
     * Parse a string based rule.
     *
     * @param  string $rules
     *
     * @return array
     */
    protected function parseRule($rules)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($rules, $parameter);
        }

        $rules = trim($rules);

        return ['is' . Str::studly($rules), $parameters, $rules];
    }

    /**
     * Parse a parameter list.
     *
     * @param  string $rule
     * @param  string $parameter
     *
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }


    public function validate($rules, $data)
    {
        $this->data = $data;
        $rules      = $this->explode($rules);
        $result     = [];
        foreach ($rules as $attribute => $val_rules) {
            foreach ($val_rules as $rule) {
                $vali = $this->parseRule($rule);
                $ret  = call_user_func_array([$this, $vali[0]], [$data[$attribute], $vali[1]]);
                if (!$ret) {
                    $replace = [
                        'attribute' => config("lang.".trans()->getLocale().".validation.attributes.{$attribute}", $attribute)
                    ];

                    $message = $this->getMessage($attribute, $vali[2], $replace);
                    if (isset($this->replaceRules[$vali[2]])) {
                        $count_replace = count($this->replaceRules[$vali[2]]);
                        if ($count_replace == count($vali[1])) {
                            $message = str_replace($this->replaceRules[$vali[2]], $vali[1], $message);
                        } elseif ($count_replace == 1) {
                            $message = str_replace($this->replaceRules[$vali[2]][0], json_encode($vali[1]), $message);
                        }
                    }

                    $result[$attribute] = $message;
                }
            }
        }

        return $result;
    }

    /**
     * Get the validation message for an attribute and rule.
     *
     * @param  string $attribute
     * @param  string $rule
     * @param  array  $replace
     *
     * @return string
     */
    protected function getMessage($attribute, $rule, $replace = [])
    {

        $custemKey = "validation.custom.{$attribute}.{$rule}";

        $custemMessage = __($custemKey, $replace);
        if ($custemMessage != $custemKey) {
            return $custemMessage;
        }

        return __("validation.{$rule}", $replace);
    }


    /**
     * Validate that an attribute passes a regular expression check.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    protected function isRegex($value, $parameters)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value) > 0;
    }


    /**
     * Validate that an attribute is an integer.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isInteger($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function isInt($value)
    {
        return $this->isInteger($value);
    }

    /**
     * Validate that an attribute is a valid IP.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv4.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isIpv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that an attribute is a valid IPv6.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isIpv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate the attribute is a valid JSON string.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isJson($value)
    {
        if (!is_scalar($value) && !method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function isMin($value, $parameter)
    {
        return $value >= $parameter[0];
    }

    public function isMax($value, $parameter)
    {
        return $value <= $parameter[0];
    }

    /**
     *    数据基础验证-检测字符串长度.
     *
     * @param string $value     需要验证的值
     * @param int    $parameter 参数
     *
     * @return bool
     */
    public function isLength($value, $parameter)
    {
        $min = $parameter[0];
        $max = $parameter[1];
        if ($min != 0 && strlen($value) < $min) {
            return false;
        }
        if ($max != 0 && strlen($value) > $max) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isAfter($value, $parameters)
    {
        if (!$this->isDate($value) || !$this->isDate($parameters[0])) {
            return false;
        }

        return strtotime($value) <= strtotime($parameters[0]);
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isBefore($value, $parameters)
    {
        if (!$this->isDate($value) || !$this->isDate($parameters[0])) {
            return false;
        }

        return strtotime($value) >= strtotime($parameters[0]);
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isConfirmed($value, $parameters)
    {
        return $value === $this->data[$parameters[0]];
    }

    /**
     * Validate the size of an attribute is between a set of values.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function validateBetween($value, $parameters)
    {
        return $value >= $parameters[0] && $value <= $parameters[1];
    }

    /**
     *    数据基础验证-是否必须填写的参数.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isRequired($value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && $value === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredIf($value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if');

        $other = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (is_bool($other)) {
            $values = $this->convertValuesToBoolean($values);
        }

        if (in_array($other, $values)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredUnless($value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless');

        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (!in_array($data, $values)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when any other attribute exists.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredWith($value, $parameters)
    {
        if (!$this->allFailingRequired($parameters)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes exists.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredWithAll($value, $parameters)
    {
        if (!$this->anyFailingRequired($parameters)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredWithout($value, $parameters)
    {
        if ($this->anyFailingRequired($parameters)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes do not.
     *
     * @param  mixed $value
     * @param  mixed $parameters
     *
     * @return bool
     */
    protected function isRequiredWithoutAll($value, $parameters)
    {
        if ($this->allFailingRequired($parameters)) {
            return $this->isRequired($value);
        }

        return true;
    }

    /**
     * Determine if any of the given attributes fail the required test.
     *
     * @param  array $attributes
     *
     * @return bool
     */
    protected function anyFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if (!$this->isRequired($this->getValue($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if all of the given attributes fail the required test.
     *
     * @param  array $attributes
     *
     * @return bool
     */
    protected function allFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if ($this->isRequired($this->getValue($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     *    数据基础验证-是否是空字符串.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isEmpty($value)
    {
        if (empty($value) || $value == '') {
            return false;
        }

        return true;
    }

    /**
     *    数据基础验证-检测数组，数组为空时候也返回FALSH.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isArray($value)
    {
        return is_array($value);
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isBoolean($value)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    public function isBool($value)
    {
        $this->isBoolean($value);
    }

    /**
     * Validate that an attribute is a valid date.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isDate($value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((!is_string($value) && !is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isDateFormat($value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $date = DateTime::createFromFormat($parameters[0], $value);

        return $date && $date->format($parameters[0]) == $value;
    }


    /**
     * Validate that an attribute is different from another attribute.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    protected function isDifferent($value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'different');

        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value !== $other;
    }

    /**
     *    数据基础验证-是否是Email 验证：xxx@qq.com.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }


    /**
     * Validate that an attribute is a string.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isString($value)
    {
        return is_string($value);
    }

    /**
     * Validate that an attribute is a valid timezone.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isTimezone($value)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Validate an attribute is contained within a list of values.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isIn($value, $parameters)
    {
        return !is_array($value) && in_array((string)$value, $parameters);
    }

    /**
     * Validate an attribute is contained within a list of values.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isNotIn($value, $parameters)
    {
        return !$this->isIn($value, $parameters);
    }

    /**
     *    数据基础验证-是否是数字类型.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isNumber($value)
    {
        return is_numeric($value);
    }


    /**
     * Validate that an attribute has a given number of digits.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isDigits($value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'digits');

        return !preg_match('/[^0-9]/', $value)
               && strlen((string)$value) == $parameters[0];
    }

    /**
     * Validate that an attribute is between a given number of digits.
     *
     * @param  mixed $value
     * @param  array $parameters
     *
     * @return bool
     */
    public function isDigitsBetween($value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string)$value);

        return !preg_match('/[^0-9]/', $value)
               && $length >= $parameters[0] && $length <= $parameters[1];
    }

    /**
     *    数据基础验证-是否是身份证
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isCard($value)
    {
        return preg_match("/^(\d{15}|\d{17}[\dx])$/i", $value);
    }

    /**
     *    数据基础验证-是否是电话 验证：0571-xxxxxxxx.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isMobile($value)
    {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', $value);
    }

    /**
     *    数据基础验证-是否是移动电话 验证：1385810XXXX.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isPhone($value)
    {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(13|15)\d{9}$/', $value);
    }

    /**
     *    数据基础验证-是否是URL 验证：http://www.easyphp.cc.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isUrl($value)
    {
        return preg_match('/^https?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/', $value);
    }

    /**
     *    数据基础验证-是否是邮政编码 验证：311100.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isZip($value)
    {
        return preg_match('/^[1-9]\d{5}$/', $value);
    }

    /**
     *    数据基础验证-是否是QQ.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isQQ($value)
    {
        return preg_match('/^[1-9]\d{4,12}$/', $value);
    }

    /**
     *    数据基础验证-是否是英文字母.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isAlpha($value)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, and underscores.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isAlphaDash($value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public function isAlphaNum($value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    /**
     *    数据基础验证-是否是中文.
     *
     * @param string $value 需要验证的值
     *
     * @return bool
     */
    public function isChinese($value)
    {
        return preg_match("/^([\xE4-\xE9][\x80-\xBF][\x80-\xBF])+$/", $value);
    }

    /**
     * Require a certain number of parameters to be present.
     *
     * @param  int    $count
     * @param  array  $parameters
     * @param  string $rule
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string $attribute
     *
     * @return mixed
     */
    protected function getValue($attribute)
    {
        return Arr::get($this->data, $attribute);
    }

    /**
     * Convert the given values to boolean if they are string "true" / "false".
     *
     * @param  array $values
     *
     * @return array
     */
    protected function convertValuesToBoolean($values)
    {
        return array_map(function ($value) {
            if ($value === 'true') {
                return true;
            } elseif ($value === 'false') {
                return false;
            }

            return $value;
        }, $values);
    }
}
