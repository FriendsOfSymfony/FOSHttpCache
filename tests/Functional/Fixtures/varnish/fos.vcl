include "debug.vcl";
include "purge.vcl";
include "refresh.vcl";
include "ban.vcl";
include "user_context_cache.vcl";
include "user_context_nocache.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl invalidators {
    "127.0.0.1";
}
