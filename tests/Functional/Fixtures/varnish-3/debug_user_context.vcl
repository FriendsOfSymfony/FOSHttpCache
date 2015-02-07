sub vcl_deliver {
    set resp.http.X-HashCache = "MISS";

    if (resp.http.content-type ~ "application/vnd.fos.user-context-hash") {
        if (obj.hits > 0) {
            set req.http.X-HashCache = "HIT";
        }
    } elsif (req.http.X-HashCache) {
        set resp.http.X-HashCache = req.http.X-HashCache;
    }
}

sub user_context_hash_url {
    # A little hack for testing all scenarios
    if ("failure" == req.http.X-Cache-Hash) {
        set req.url = "/user_context_hash_failure.php";
    } elsif (req.http.X-Cache-Hash) {
        set req.url = "/user_context_hash_cache.php";
    } else {
        set req.url = "/user_context_hash_nocache.php";
    }
}
