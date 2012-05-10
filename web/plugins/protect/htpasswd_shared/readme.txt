<b>HTPASSWD_SHARED protect plugin</b>

Password protect directories with this plugin. It generates .htpasswd file 
at specific location. This plugin is different from htpasswd - this plugin
never rebuild .htpasswd file, it only adds/deletes record from it. 
htpasswd_shared plugin is useful when you already have .htpasswd file managed
by iBill, ccBill, VeroTel or something like this. It will be safe to use
this plugin in conjunction with that service.
If you don't have existing .htpasswd files, use "htpasswd" plugin instead, 
and disable this plugin completely.

NOTE: this plugins supports only one level of access.

NOTE: rebuild db will add all active members from aMember Db to .htpasswd
file. If user with the same username is already exists in .htpasswd, password
will be changed to password from aMember Db.

NOTE: if username is already exists in .htpasswd file, users will not be 
allowed to signup with the same username.
