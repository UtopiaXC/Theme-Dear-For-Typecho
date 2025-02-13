# Theme-Dear-For-Typecho
这是一款基于[Dear](https://github.com/imjeff/typecho-dear)修改而来的Typecho主题。  
原README请前往[原仓库](https://github.com/imjeff/typecho-dear)查看。  
开源协议：CC BY-NC-SA 4.0 DEED（与原仓库相同）  

我的博客兼Demo：[UtopiaXC Blog](https://blog.utopiaxc.cn/)  

另外，评论功能UI大范围借鉴[Story-for-Typecho
](https://github.com/txperl/Story-for-Typecho)主题，在此表示十分感谢。  
同时本项目也将也遵守其开源许可。  

# 新特性
1. 重做评论系统  
2. 添加自定义配置文件  
3. 添加友链系统（需要配合[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件使用） 
4. 添加分类与标签模板 
   
# 使用方法
1. 安装  
   a. 请从[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本，将其解压后的Dear-For-Typecho文件夹放置于您的typecho站点下的`usr/themes/`文件夹中。  
   b. 打开您的typecho管理后台，在控制台→外观中启用Dear-For-Typecho主题。
2. 自定义  
   请在typecho管理后台中的控制台→外观→编辑当前外观中修改config.php，请按照注释对参数进行修改。

# 注意  
1. 请不要直接下载本项目源代码进行安装，请在正式版发布后在[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本。  
2. 如果您使用了源代码进行部署，需要将主题文件夹名更改为Dear-For-Typecho，并且建议将根目录下的demo文件夹删除，以削减服务器中存在的不必要的文件。  
3. 友情链接功能强依赖于[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件，请务必安装，否则友情链接无法正常显示。  
4. MySQL8以后不再支持MyISAM引擎，因此使用MySQL8以上时开启Links插件报错HY000的话，请将`plugin/Links/Mysql.sql`中的MyISAM更改为InnoDB。  

# Demo  
可以前往我的博客查看效果：[UtopiaXC Blog](https://blog.utopiaxc.cn/)

## 1. 主页  
![主页日间](./demo/index(day).png)  
![主页夜间](./demo/index(night).png)  
## 2. 文章页
![文章页日间](./demo/article(day).png)  
![文章页夜间](./demo/article(night).png)  
## 3. 友情链接
![友情链接日间](./demo/links(day).png)  
![友情链接夜间](./demo/links(night).png)  