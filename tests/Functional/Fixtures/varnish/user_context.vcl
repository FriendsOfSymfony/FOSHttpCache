## generically handle user context. needs to be included from a vcl that
## defines a sub fos_handle_context that adds the right backend url.

sub vcl_recv {
    if (req.restarts == 0
        && (req.http.cookie || req.http.authentication)
        && (req.request == "GET" || req.request == "HEAD")
    ) {
        set req.http.x-original-url    = req.url;
        set req.http.x-original-accept = req.http.accept;

        set req.http.accept            = "application/vnd.fos.user-context-hash";

        call fos_handle_context;
    } elsif (req.restarts > 0 && req.http.accept ~ "application/vnd.fos.user-context-hash") {
        set req.url         = req.http.x-original-url;
        set req.http.accept = req.http.x-original-accept;

        unset req.http.x-original-url;
        unset req.http.x-original-accept;

        # We do the original request with the user hash as provided by the backend.
        # We lookup in the cache even when the Cookie or Authorization header are present.
        # It is the responsibility of the backend to Vary on the user hash to
        # properly separate cached data.

        return (lookup);
    }
}

sub vcl_deliver {
    set resp.http.X-HashCache = "MISS";

    # After receiving the hash response, copy the hash header
    # to the original request and restart it.

    if (resp.http.content-type ~ "application/vnd.fos.user-context-hash") {
        set req.http.x-user-context-hash = resp.http.x-user-context-hash;

        if (obj.hits > 0) {
            set req.http.X-HashCache = "HIT";
        } else {
            set req.http.X-HashCache = "MISS";
        }

        return (restart);
    } elsif (req.http.X-HashCache) {
        set resp.http.X-HashCache = req.http.X-HashCache;
    }
}
