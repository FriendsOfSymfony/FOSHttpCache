vcl 4.0;

# Re-use Varnish 3 VCL
include "../varnish-3/refresh.vcl";

# These need changes for Varnish 4
include "debug.vcl";
include "purge.vcl";
include "ban.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl invalidators {
    "127.0.0.1";
}
