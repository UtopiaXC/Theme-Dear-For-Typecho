# Theme-Dear-For-Typecho
这是一款基于[Dear](https://github.com/imjeff/typecho-dear)修改而来的Typecho主题。  
原README请前往[原仓库](https://github.com/imjeff/typecho-dear)查看。  
开源协议：CC BY-NC-SA 4.0 DEED（与原仓库相同）  

我的博客兼Demo：[UtopiaXC Blog](https://blog.utopiaxc.cn/)  

评论功能UI大范围借鉴[Story-for-Typecho
](https://github.com/txperl/Story-for-Typecho)主题，在此表示十分感谢。同时本项目也将也遵守其开源许可。  

自动检测渲染LaTeX功能参考文章《[为 Typecho 增加 LaTeX 公式的渲染](https://nwdan.com/tutorials/typecho-latex-support.html)》

为维持与原主题一致性，新特性均需要手动在config中开启。目前需要手动修改config.php，较为麻烦，预期在下一版本中添加一个设置页面来简化修改流程。    

# 新特性
1. 添加评论UI（默认关闭，需要在config中开启）  
2. 添加自定义配置文件  
3. 添加友链系统（需要配合[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件使用，如果有不通过Links插件直接进行markdown内容解析的需求可以提issue，我会考虑添加该功能） 
4. 添加分类与标签模板  
5. 添加viewerjs灯箱（默认关闭，需要在config中开启）  
6. 添加highlightjs代码高亮（默认关闭，需要在config中开启）  
7. 添加KaTeX作为LaTeX渲染引擎（发表文章时进行选择是否渲染）

# 使用方法
1. 安装  
   a. 请从[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本，将其解压后的Dear-For-Typecho文件夹放置于您的typecho站点下的`usr/themes/`文件夹中。  
   b. 打开您的typecho管理后台，在控制台→外观中启用Dear-For-Typecho主题。
2. 自定义  
   请在typecho管理后台中的控制台→外观→编辑当前外观中修改config.php，请按照注释对参数进行修改。  

# 支持自定义的Config
- 是否在文章列表中显示文章分类
- 文章列表每页包含条目数
- 主页显示的嵌套独立页面
- 是否启用评论区
- 是否启用评论时输入网址
- 友情链接头像默认图片链接
- 是否在新页面内打开友情链接
- 没有分类的友情链接的默认分类名
- 是否显示面包屑导航
- 是否在面包屑导航中显示首页
- 是否在面包屑导航中显示全部独立页面
- 面包屑导航中显示的独立页面名数组
- 首页文章列表的标题
- 是否启用viewerjs灯箱
- RSS链接
- 是否启用highlightjs代码高亮库

另外，如果希望添加自定义css与js，比如访问统计，请将代码放置于asset/css/custom.css与asset/js/custom.js中。  

为节省资源与提高加载速度，不开启的功能将不会载入对应CSS与JS。

# 独立页面模板
请在独立页面设置中选择对应的自定义模板，支持的模板如下：
- 全部分类与标签：该模板将会显示博客中添加过的全部分类与标签。
- 友情链接：该页面将会显示您在Links插件中添加的友情链接。
- 搜索：一个简单的搜索页面。

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