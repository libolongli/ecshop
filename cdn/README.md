*************************************************************************************************************************
*1 请先配置 include/cdnconfig.php 这个文件 此模块才能正常运行(注意:文件中的路径都是相对									*
*	于网站根目录的,暂时不支持网站根目录意外的文件,并且要注意设置的文件夹的权限)											*
*2 配置完成后运行install.php 进行数据库的安装																			*
*3 再在命令行或者网站访问index.php进行上传                                                                              *
*                                                                                                                       *
*************************************MIND*********************************                                              *
*暂时只支持upyun和7niu 上传 如果想扩展其他的CDN请扩展 index.php                                                         *
*(1) 在文件进行上传之后会将上传文件的文件路径,上传日期,文件的MD5,文件类型,文件空间写入到数据库里面当作流水和再次上传的校*
*   验证使用                                                                                                            *
*(2)当同目录再次上传的时候会先进行MD5校验,如果MD5值一样则不进行操作,如果不一样会执行覆盖CDN上上面的文件,并跟新数据库对应*
*   的原来的数据                                                                                                        *
*                                                                                                                       *
*  date 2014/04/03  author : Dick                                                                                       *
*************************************************************************************************************************