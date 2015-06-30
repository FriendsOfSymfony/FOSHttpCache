include "../../../../resources/config/varnish-3/fos_user_context.vcl";
include "../../../../resources/config/varnish-3/fos_debug.vcl";

sub vcl_recv {
    call fos_user_context_recv;
}

sub vcl_fetch {
    call fos_user_context_fetch;
}

sub vcl_deliver {
    call fos_debug_deliver;
    call fos_user_context_deliver;
}