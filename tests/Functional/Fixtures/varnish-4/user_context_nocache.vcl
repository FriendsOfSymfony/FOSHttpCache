vcl 4.0;

include "debug.vcl";
include "../varnish-3/debug_user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

include "user_context.vcl";
