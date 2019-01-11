# Buto-Plugin-HelpText_v1
Show helptext and let user confirm. Webadmin can edit text.


In theme config/settings.yml.


```
plugin:
  help:
    text_v1:
      enabled: true
      data:
        mysql: 'yml:/_pat_to_/mysql.yml'
```

```
events:
  signin:
    -
      plugin: 'help/text_v1'
      method: 'signin'

```



Add widget where helptext are to be placed. Set unic data/data/id for the helptext


```
-
  type: widget
  data:
    plugin: 'help/text_v1'
    method: helptext
    data:
      id: _id_for_my_helptext_
```

Add page plugin where user can view items.

```
plugin_modules:
  helptext:
    plugin: 'help/text_v1'
```


```
PluginWfBootstrapjs.modal({id: 'modal_helptext', url: '/helptext/helptext', lable: 'Helptext', footer_btn_close: true})
```
