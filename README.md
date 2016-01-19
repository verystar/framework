#The Verystar php framework

##说明
这是一个简单的遵循PSR-4的框架，借鉴了Laravel的DI思想，目前为了兼容老的项目代码精简了Laravel的设计，后续会逐步的完善

##Install

你可以使用composer来安装框架，创建composer.json文件，内容如下：

```
{
  "name": "fifsky/very",
  "description": "The Verystar php framework",
  "minimum-stability": "stable",
  "license": "MIT",
  "repositories": [
    {
      "type": "git",
      "url": "git@git.verystar.cn:fifsky/very.git"
    }
  ],
  "authors": [
    {
      "name": "fifsky",
      "email": "caixudong@verystar.cn"
    }
  ],
  "require": {
    "php": ">=5.4.0",
    "fifsky/very": "1.*",
  }
}

```

执行`composer install`来安装，然后引入include PATH . '/vendor/autoload.php'