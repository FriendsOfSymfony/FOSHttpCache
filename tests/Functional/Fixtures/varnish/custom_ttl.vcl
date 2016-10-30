vcl 4.0;

include "../../../../resources/config/varnish/fos_debug.vcl";
include "../../../../resources/config/varnish/fos_custom_ttl.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_deliver {
    call fos_debug_deliver;
}

sub vcl_backend_response {
    call fos_custom_ttl_backend_response;
}
