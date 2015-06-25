vcl 4.0;

include "debug.vcl";
include "../varnish-3/debug_user_context.vcl";
include "../../../../config/varnish-4/fos_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_recv {
    call fos_user_context_recv;
}

sub vcl_backend_response {
    call fos_user_context_backend_response;
}

sub vcl_deliver {
    call fos_user_context_deliver;
}
