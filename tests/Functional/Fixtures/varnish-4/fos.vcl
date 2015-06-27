vcl 4.0;

include "../../../../resources/config/varnish-4/fos_debug.vcl";
include "../../../../resources/config/varnish-4/fos_refresh.vcl";
include "../../../../resources/config/varnish-4/fos_purge.vcl";
include "../../../../resources/config/varnish-4/fos_ban.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl invalidators {
    "127.0.0.1";
}

sub vcl_recv {
    call fos_ban_recv;
    call fos_purge_recv;
    call fos_refresh_recv;
}

sub vcl_backend_response {
    call fos_ban_backend_response;
}

sub vcl_deliver {
    call fos_debug_deliver;
    call fos_ban_deliver;
}
