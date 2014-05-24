include "debug.vcl";
include "user_context.vcl";

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub fos_handle_context {
    set req.url = "/user_context_hash_cache.php";

    # By default, Varnish does not look for cache when a Cookie or
    # Authorization header is present.
    # See: https://www.varnish-cache.org/trac/browser/bin/varnishd/default.vcl?rev=3.0#L63
    #
    # We force the lookup, the backend must vary on all relevant headers used to build the hash.

    return (lookup);
}
