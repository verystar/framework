# The Verystar php framework

## 说明
这是一个简单的遵循PSR-4的框架，借鉴了Laravel的DI思想，采用了优秀的开源组件(symfony,monolog)设计的轻量级MVC框架，目前为了兼容老的项目代码精简了Laravel的设计，后续会逐步的完善

## Install

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
      "url": "git@git.verystar.cn:very/framework.git"
    }
  ],
  "authors": [
    {
      "name": "fifsky",
      "email": "caixudong@verystar.cn"
    }
  ],
  "require": {
    "php": ">=5.5.0",
    "fifsky/very": "~1.0"
  }
}

```

执行`composer install`来安装，然后引入include PATH . '/vendor/autoload.php'

## 框架案例
https://git.verystar.cn/very/very

## 待完善功能

- Logger支持多种记录，系统日志记录
- Upload上传组件完善
- Debug类完善
- Sqlite数据库连接器完善

## 待开发功能

- Event事件监听模型开发

## 测试

```
phpunit --bootstrap tests/bootstrap.php tests
```

> 目前测试文档还没有来得及编写，欢迎补充测试文档