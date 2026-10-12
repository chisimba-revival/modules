# Blog is paused

Derek paused this historical Blog module on 11 October 2026. It is distinct from
the maintained SimpleBlog module. Keep stored data; do not reactivate by simply
renaming this directory.

The shared PEAR XML-RPC library has been removed. Historical blogxmlrpc/blogmail
classes still reference it. Before reactivation, review the module for PHP 8,
authorisation, native shared services and dbTable persistence, and retire or
replace those remote integrations. Do not restore the obsolete shared library.
