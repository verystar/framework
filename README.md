<p align="center"><a href="http://verystar.cn" target="_blank"><img src="https://static.verystar.cn/s/logo.png"></a></p>
<p align="center">
<a href="https://travis-ci.org/very/framework"><img src="https://api.travis-ci.org/verystar/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/very/framework"><img src="https://poser.pugx.org/very/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/very/framework"><img src="https://poser.pugx.org/very/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/very/framework"><img src="https://poser.pugx.org/very/framework/license.svg" alt="License"></a>
</p>

# The Verystar php framework

## 说明
这是一个简单的遵循PSR-4的框架，借鉴了Laravel的DI思想，采用了优秀的开源组件(symfony,monolog)设计的轻量级MVC框架，目前为了兼容老的项目代码精简了Laravel的设计，后续会逐步的完善

## Install

你可以使用composer来安装框架，添加依赖到composer.json文件，内容如下：

```
"require": {
    "very/framework": "2.8.*"
}
```

执行`composer install`来安装，然后引入include PATH . '/vendor/autoload.php'

## 框架案例
https://github.com/verystar/very

## 待完善功能
- Sqlite数据库连接器完善

## 待开发功能

- Event事件监听模型开发

## 测试

```
 ./vendor/bin/phpunit
```

> 目前测试文档还没有来得及编写，欢迎补充测试文档

## 感谢

- [Laravel](https://github.com/laravel/framework)
- [symfony](https://github.com/symfony/symfony)
- [monolog](https://github.com/Seldaek/monolog)

## License
The Very framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
