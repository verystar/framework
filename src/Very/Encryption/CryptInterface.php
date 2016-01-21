<?php
namespace Very\Encryption;
/**
 * 加密接口，开发者需要开发新的加密驱动需要实现该接口
 */
interface CryptInterface {
    /**
     * 加密字符串
     * CryptService::encode('xsdsd',86400);加密xsdsd数据。时效为1天
     *
     * @param string $data   加密的字符串
     * @param int    $expire 加密时长 为0 的话永久有效的
     *
     * @return mixed
     */
    public function encode($data, $expire = 0);

    /**
     * 解密
     * echo CryptService::decode($data);//如果时效过期，则发货null
     *
     * @param string $data 加密后的字符串
     *
     * @return mixed
     */
    public function decode($data);
}