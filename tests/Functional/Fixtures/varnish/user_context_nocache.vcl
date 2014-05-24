include "debug.vcl";
include "user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub fos_handle_context {
    set req.url = "/user_context_hash_nocache.php";
}
