# Buto-Plugin-HelpText_v1
Show a button with helptext label. When user click description is shown and a session param is set. Next time buttons shows up a tick icon are visible. User can also click a button GOT IT to permanently has this ticket. A record i db is stored in this case.




## Settings
In theme config/settings.yml.
```
plugin:
  help:
    text_v1:
      enabled: true
      data:
        mysql: 'yml:/_pat_to_/mysql.yml'
```
To place GOT IT actions in session one must add this event.
```
events:
  signin:
    -
      plugin: 'help/text_v1'
      method: 'signin'

```

## Widget
Add widget where helptext are to be placed. Set unic data/data/id for the helptext.
```
type: widget
data:
  plugin: 'help/text_v1'
  method: helptext
  data:
    id: _id_for_my_helptext_
```

## Page
Add page plugin where user can view items.

```
plugin_modules:
  helptext:
    plugin: 'help/text_v1'
```
Show this page in a modal.
```
PluginWfBootstrapjs.modal({id: 'modal_helptext', url: '/helptext/helptext', label: 'Helptext', footer_btn_close: true})
```

## Schema
```
/mysql/schema.yml
```
