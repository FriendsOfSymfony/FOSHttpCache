include "../../../../resources/config/varnish/fos_user_context.vcl";
include "../../../../resources/config/varnish/fos_debug.vcl";

sub vcl_recv {
    call fos_user_context_recv;
}

sub vcl_backend_response {
    call fos_user_context_backend_response;
}

sub vcl_deliver {
    call fos_debug_deliver;
    call fos_user_context_deliver;
}
