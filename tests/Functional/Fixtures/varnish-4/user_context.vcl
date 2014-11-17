vcl 4.0;

sub vcl_recv {

    # Prevent tampering attacks on the hash mechanism
    if (req.restarts == 0
        && (req.http.accept ~ "application/vnd.fos.user-context-hash"
            || req.http.x-user-context-hash
        )
    ) {
        return (synth(400));
    }

    # Lookup the context hash if there are credentials on the request
    if (req.restarts == 0
        && (req.http.cookie || req.http.authorization)
        && (req.method == "GET" || req.method == "HEAD")
    ) {
        set req.http.x-fos-original-url = req.url;
        # Backup accept header, if set
        if (req.http.accept) {
            set req.http.x-fos-original-accept = req.http.accept;
        }
        set req.http.accept = "application/vnd.fos.user-context-hash";

        # A little hack for testing all scenarios. Choose one for your application.
        if ("failure" == req.http.x-cache-hash) {
            set req.url = "/user_context_hash_failure.php";
        } elsif (req.http.x-cache-hash) {
            set req.url = "/user_context_hash_cache.php";
        } else {
            set req.url = "/user_context_hash_nocache.php";
        }

        # Force the lookup, the backend must tell not to cache or vary on all
        # headers that are used to build the hash.

        return (hash);
    }

    # Rebuild the original request which now has the hash.
    if (req.restarts > 0
        && req.http.accept == "application/vnd.fos.user-context-hash"
    ) {
        set req.url = req.http.x-fos-original-url;
        unset req.http.x-fos-original-url;
        if (req.http.x-fos-original-accept) {
            set req.http.accept = req.http.x-fos-original-accept;
            unset req.http.x-fos-original-accept;
        } else {
            # If accept header was not set in original request, remove the header here.
            unset req.http.accept;
        }

        # Force the lookup, the backend must tell not to cache or vary on the
        # user hash to properly separate cached data.

        return (hash);
    }
}

sub vcl_backend_response {
    if (bereq.http.accept ~ "application/vnd.fos.user-context-hash"
        && beresp.status >= 500
    ) {
        return (abandon);
    }
}

sub vcl_deliver {
    # On receiving the hash response, copy the hash header to the original
    # request and restart.
    if (req.restarts == 0
        && resp.http.content-type ~ "application/vnd.fos.user-context-hash"
    ) {
        set req.http.x-user-context-hash = resp.http.x-user-context-hash;

        return (restart);
    }

    # If we get here, this is a real response that gets sent to the client.

    # Remove the vary on context user hash, this is nothing public. Keep all
    # other vary headers.
    set resp.http.Vary = regsub(resp.http.Vary, "(?i),? *x-user-context-hash *", "");
    set resp.http.Vary = regsub(resp.http.Vary, "^, *", "");
    if (resp.http.Vary == "") {
        unset resp.http.Vary;
    }

    # Sanity check to prevent ever exposing the hash to a client.
    unset resp.http.x-user-context-hash;
}
