# Buto-Plugin-HelpText_v1
- Show a button with helptext label where to show help text.
- When user click button it remainds dimed rest of the session.
- User can click button "Got it" to made button dimed permanently (saved in db).
- User can clear in db.
- A video could be added.
- User with role webadmin could add/edit posts.

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
