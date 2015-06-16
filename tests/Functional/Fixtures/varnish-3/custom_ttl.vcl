include "../../../../resources/config/varnish-3/fos_debug.vcl";
include "../../../../resources/config/varnish-3/fos_custom_ttl.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}
sub vcl_fetch {
    call fos_custom_ttl_fetch;
}

sub vcl_deliver {
    call fos_debug_deliver;
}
