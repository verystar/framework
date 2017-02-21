<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 14/02/2017 3:41 PM
 */

namespace Very\Http;

abstract class FormRequest
{

    /**
     * authorize validation
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function validate()
    {
        $error = app('validation')->validate($this->rules(), request()->all());
        if ($error) {
            $this->response($error);
        }
    }

    /**
     * validation rules
     * @return mixed
     */
    abstract public function rules();

    /**
     * authorize response
     * @return mixed
     */
    abstract public function forbiddenResponse();

    /**
     * rule faild response
     * @param array $error
     *
     * @return mixed
     */
    abstract public function response(array $error);
}