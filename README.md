# Theme-Dear-For-Typecho
这是一款基于[Dear](https://github.com/imjeff/typecho-dear)修改而来的Typecho主题。
原README请前往[原仓库](https://github.com/imjeff/typecho-dear)查看。  


更多详情请查看[我的博客专题文章](https://blog.utopiaxc.cn/2026/04/theme3)  
我的博客兼Demo：[UtopiaXC Blog](https://blog.utopiaxc.cn/)  



# 新特性
1. 评论  
2. 友链、分类与标签独立页面模板  
3. Viewer.js灯箱、Highlight.js代码高亮、LaTeX等阅读增强功能  
4. 目录与AI摘要功能  

# 使用方法
1. 安装  
   a. 请从[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本，将其解压后的Dear-For-Typecho文件夹放置于您的typecho站点下的`usr/themes/`文件夹中。  
   b. 打开您的typecho管理后台，在控制台→外观中启用Dear-For-Typecho主题。
2. 自定义  
   请在typecho管理后台中的控制台→外观→设置外观中修改可自定义的项目  
3. 更多使用方法请参考博客文章《[Dear主题3版本正式发布](https://blog.utopiaxc.cn/2026/04/theme3)》。文章可能会实时更新，如果有内容变动请及时参考。  

# Demo  
可以前往我的博客查看效果：[UtopiaXC Blog](https://blog.utopiaxc.cn/)  
以下为部分效果截图：  
## 1. 主页  
![主页日间](./demo/index(day).png)  
![主页夜间](./demo/index(night).png)  
## 2. 文章页
![文章页日间](./demo/article(day).png)  
![文章页夜间](./demo/article(night).png)  

## 3. 可设置项
![设置项](./demo/settings.png)

# 注意
1. 请不要直接下载本项目源代码进行安装，请在正式版发布后在[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本。
2. 如果您使用了源代码进行部署，需要将主题文件夹名更改为Dear-For-Typecho，并且建议将根目录下的demo文件夹删除，以削减服务器中存在的不必要的文件。
3. 2.x版本友情链接功能强依赖于[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件，请务必安装，否则友情链接无法正常显示。MySQL8以后不再支持MyISAM引擎，因此使用MySQL8以上时开启Links插件报错HY000的话，请将`plugin/Links/Mysql.sql`中的MyISAM更改为InnoDB。
5. 如果您从2.x升级到3.x或更高版本，请务必保存并备份现有的config.php。从3.x版本开始修改了配置文件的保存方式，因此请一定不要盲目升级。请务必保存并备份现有的自定义css和js文件。从3.x版本开始，将不再支持从文件中读取自定义代码，如果希望使用自定义代码，请直接在主题设置中添加。


# 开源与鸣谢
评论功能UI大范围借鉴[Story-for-Typecho
](https://github.com/txperl/Story-for-Typecho)主题，在此表示十分感谢。同时本项目也将也遵守其开源许可。

自动检测渲染LaTeX功能参考文章《[为 Typecho 增加 LaTeX 公式的渲染](https://nwdan.com/tutorials/typecho-latex-support.html)》

目录功能参考文章《[Typecho 主题文章目录（TOC）实现](https://skywt.cn/blog/typecho-toc-practice)》  

开源协议：CC BY-NC-SA 4.0 DEED（与原仓库相同）  

本项目欢迎社区协助，欢迎提交Issue与PR

# 捐助
请不要在任何渠道以任何方式为本项目付出金钱。  
如果您想捐助本项目，您可以向慈善组织或开放源代码促进会（开源组织，OSI）捐款，我们会感激不尽。