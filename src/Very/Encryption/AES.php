<?php
namespace Very\Encryption;
/**
 * AES 256加密
 */
class AES extends ThirdDes
{
    protected $ivLength = 32;
    protected $keyLength = 32;
    protected $type = MCRYPT_RIJNDAEL_256;
}