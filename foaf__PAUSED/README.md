# FOAF is paused

Derek paused this historical social-network module on 12 October 2026.
Keep its stored data; do not enable it merely by renaming the directory.

The framework no longer bundles PEAR RDF, MDB or Validate. The old FOAF parser
still references these retired dependencies. Before reactivation, review the
whole module for PHP 8 compatibility, permissions, safe XML handling and native
shared services. All persistence must use dbTable. Rebuild its parser as needed;
do not restore obsolete libraries to the shared runtime.
