# Theme-Dear-For-Typecho
这是一款基于[Dear](https://github.com/imjeff/typecho-dear)修改而来的Typecho主题。  
原README请前往[原仓库](https://github.com/imjeff/typecho-dear)查看。  
开源协议：CC BY-NC-SA 4.0 DEED（与原仓库相同）  

我的博客兼Demo：[UtopiaXC Blog](https://blog.utopiaxc.cn/)  

评论功能UI大范围借鉴[Story-for-Typecho
](https://github.com/txperl/Story-for-Typecho)主题，在此表示十分感谢。同时本项目也将也遵守其开源许可。  

自动检测渲染LaTeX功能参考文章《[为 Typecho 增加 LaTeX 公式的渲染](https://nwdan.com/tutorials/typecho-latex-support.html)》   

# 新特性
1. 添加评论UI
2. 添加自定义配置文件  
3. 添加友链系统
4. 添加分类与标签模板  
5. 添加viewerjs灯箱
6. 添加highlightjs代码高亮
7. 添加KaTeX作为LaTeX渲染引擎（发表文章时进行选择是否渲染）
8. 添加主题设置页面

# 使用方法
1. 安装  
   a. 请从[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本，将其解压后的Dear-For-Typecho文件夹放置于您的typecho站点下的`usr/themes/`文件夹中。  
   b. 打开您的typecho管理后台，在控制台→外观中启用Dear-For-Typecho主题。
2. 自定义  
   请在typecho管理后台中的控制台→外观→设置外观中修改可自定义的项目  

# 独立页面模板
请在独立页面设置中选择对应的自定义模板，支持的模板如下：
- 全部分类与标签：该模板将会显示博客中添加过的全部分类与标签。
- 友情链接：该页面将会显示您在Links插件中添加的友情链接。
- 搜索：一个简单的搜索页面。

## 友情链接使用方法  
友情链接支持两种添加方式：Links插件与标签解析。这两种方式可以同时存在，按照先标签后Links的顺序来显示。
### Links插件
下载并启用[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件，然后在后台添加友情链接。  
注意，Links插件版本过老，可能和MySQL8数据库存在冲突，请自行修改Links插件中的数据库引擎来解决。详见下方注意章节。  
### 标签解析
在使用了友情链接模板的独立页面的内容中，任意位置添加以下标签
```html
<dear-links>

</dear-links>
```
标签内部，需要一个JSON数组。数组中元素需要包含链接信息，格式如下：
```json
[
    {
        "type": "Search",
        "name": "Google",
        "url": "https://www.google.com/",
        "description": "Google",
        "image": "https://google.png",
        "email": "google@google.com",
        "display": true,
    }
]
```
如果条目中不包含某个字段，可以直接留空不写。  
渲染时将按照type来进行分类整合。  
# 注意  
1. 请不要直接下载本项目源代码进行安装，请在正式版发布后在[release](https://github.com/UtopiaXC/Theme-Dear-For-Typecho/releases)中下载最新版本。  
2. 如果您使用了源代码进行部署，需要将主题文件夹名更改为Dear-For-Typecho，并且建议将根目录下的demo文件夹删除，以削减服务器中存在的不必要的文件。  
3. 友情链接功能强依赖于[Links](https://github.com/Mejituu/Links?tab=readme-ov-file)插件，请务必安装，否则友情链接无法正常显示。  
4. MySQL8以后不再支持MyISAM引擎，因此使用MySQL8以上时开启Links插件报错HY000的话，请将`plugin/Links/Mysql.sql`中的MyISAM更改为InnoDB。  
5. 如果您从2.x升级到3.x或更高版本，请务必保存并备份现有的config.php。从3.x版本开始修改了配置文件的保存方式，因此请一定不要盲目升级。  
6. 如果您从2.x升级到3.x或更高版本，请务必保存并备份现有的自定义css和js文件。从3.x版本开始，将不再支持从文件中读取自定义代码，如果希望使用自定义代码，请直接在主题设置中添加。  

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