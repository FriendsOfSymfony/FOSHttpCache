sub vcl_recv {
    if (req.restarts == 0 && (req.http.cookie || req.http.authentication) && (req.request == "GET" || req.request == "HEAD") && req.http.x-hash-cache) {
        set req.http.x-original-url    = req.url;
        set req.http.x-original-accept = req.http.accept;

        set req.http.accept            = "application/vnd.fos.user-context-hash";
        set req.url                    = "/user_context_hash_cache.php";

        return (lookup);
    } elsif (req.restarts > 0 && req.http.accept ~ "application/vnd.fos.user-context-hash") {
        set req.url         = req.http.x-original-url;
        set req.http.accept = req.http.x-original-accept;

        unset req.http.x-original-url;
        unset req.http.x-original-accept;

        return (lookup);
    }
}

sub vcl_deliver {
    set resp.http.X-HashCache = "MISS";

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
