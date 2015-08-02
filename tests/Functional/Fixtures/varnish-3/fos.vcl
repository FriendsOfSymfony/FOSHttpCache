include "../../../../resources/config/varnish-3/fos_debug.vcl";
include "../../../../resources/config/varnish-3/fos_refresh.vcl";
include "../../../../resources/config/varnish-3/fos_purge.vcl";
include "../../../../resources/config/varnish-3/fos_ban.vcl";
include "../../../../resources/config/varnish-3/fos_custom_ttl.vcl";

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

sub vcl_fetch {
    call fos_ban_fetch;
    call fos_custom_ttl_fetch;
}

sub vcl_hit {
    call fos_purge_hit;
}

sub vcl_miss {
    call fos_purge_miss;
}

sub vcl_deliver {
    call fos_debug_deliver;
    call fos_ban_deliver;
}
