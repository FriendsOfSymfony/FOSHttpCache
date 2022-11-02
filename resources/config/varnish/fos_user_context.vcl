/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

sub fos_user_context_recv {

    # Prevent tampering attacks on the hash mechanism
    if (req.restarts == 0
        && (req.http.accept ~ "application/vnd.fos.user-context-hash"
            || req.http.X-User-Context-Hash
        )
    ) {
        return (synth(400));
    }

    # Lookup the context hash if there are credentials on the request
    # Note that the hash lookup discards the request body.
    # https://www.varnish-cache.org/trac/ticket/652
    if (req.restarts == 0
        && (req.method == "GET" || req.method == "HEAD")
    ) {
        # Backup accept header, if set
        if (req.http.accept) {
            set req.http.X-Fos-Original-Accept = req.http.accept;
        }
        set req.http.accept = "application/vnd.fos.user-context-hash";

        # Backup original URL.
        #
        # We do not use X-Original-Url here, as the header will be sent to the
        # backend and X-Original-Url has semantical meaning for some applications.
        # For example, the Microsoft IIS rewriting module uses it, and thus
        # frameworks like Symfony also have to handle that header to integrate with IIS.

        set req.http.X-Fos-Original-Url = req.url;

        call user_context_hash_url;

        # Force the lookup, the backend must tell not to cache or vary on all
        # headers that are used to build the hash.
        #
        # To avoid massive performance issues when caching the hash lookup request, see
        # fos_user_context_hash

        return (hash);
    }

    # Rebuild the original request which now has the hash.
    if (req.restarts > 0
        && req.http.accept == "application/vnd.fos.user-context-hash"
    ) {
        set req.url = req.http.X-Fos-Original-Url;
        unset req.http.X-Fos-Original-Url;
        if (req.http.X-Fos-Original-Accept) {
            set req.http.accept = req.http.X-Fos-Original-Accept;
            unset req.http.X-Fos-Original-Accept;
        } else {
            # If accept header was not set in original request, remove the header here.
            unset req.http.accept;
        }

        # Force the lookup, the backend must tell not to cache or vary on the
        # user hash to properly separate cached data.

        return (hash);
    }
}

/**
 * When caching the hash lookup request with a session or basic auth, we should include that
 * information in the hash.
 *
 * If we would only rely on Varnish keeping the variants of the response apart with the Vary
 * header, Varnish has to lookup the right variant. With a large number of users, this is extremly
 * inefficient as Varnish does not optimize Variant search and we get O(n) on the number of users.
 */
sub fos_user_context_hash {
    if (req.http.accept == "application/vnd.fos.user-context-hash") {
        hash_data(req.http.Cookie);
        hash_data(req.http.Authorization);
    }
}

sub fos_user_context_backend_response {
    if (bereq.http.accept ~ "application/vnd.fos.user-context-hash"
        && beresp.status >= 500
    ) {
        return (abandon);
    }
}

sub fos_user_context_deliver {
    # On receiving the hash response, copy the hash header to the original
    # request and restart.
    if (req.restarts == 0
        && resp.http.content-type ~ "application/vnd.fos.user-context-hash"
    ) {
        set req.http.X-User-Context-Hash = resp.http.X-User-Context-Hash;

        return (restart);
    }

    # If we get here, this is a real response that gets sent to the client and we do some cleanup if not in debug.

    if (!resp.http.X-Cache-Debug) {
        # Remove the vary on context user hash, this is nothing public. Keep all
        # other vary headers.
        set resp.http.Vary = regsub(resp.http.Vary, "(?i),? *X-User-Context-Hash *", "");
        set resp.http.Vary = regsub(resp.http.Vary, "^, *", "");
        if (resp.http.Vary == "") {
            unset resp.http.Vary;
        }

        # Sanity check to prevent ever exposing the hash to a client.
        unset resp.http.X-User-Context-Hash;
    }
}
