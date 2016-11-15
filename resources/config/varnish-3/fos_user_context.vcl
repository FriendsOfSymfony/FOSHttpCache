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
        error 400;
    }

    # Lookup the context hash if there are credentials on the request
    # Note that the hash lookup discards the request body.
    # https://www.varnish-cache.org/trac/ticket/652
    if (req.restarts == 0
        && (req.request == "GET" || req.request == "HEAD")
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
        return (lookup);
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

        return (lookup);
    }
}

sub fos_user_context_fetch {
    if (req.restarts == 0
        && req.http.accept ~ "application/vnd.fos.user-context-hash"
        && beresp.status >= 500
    ) {
        error 503 "Hash error";
    }
}

sub fos_user_context_deliver {
    # On receiving the hash response, copy the hash header to the original
    # request and restart.
    if (req.restarts == 0
        && resp.http.content-type ~ "application/vnd.fos.user-context-hash"
        && resp.status == 200
    ) {
        set req.http.X-User-Context-Hash = resp.http.X-User-Context-Hash;

        return (restart);
    }

    # If we get here, this is a real response that gets sent to the client.

    # Remove the vary on context user hash, this is nothing public. Keep all
    # other vary headers.
    set resp.http.Vary = regsub(resp.http.Vary, "(?i),? *X-User-Context-Hash *", "");
    set resp.http.Vary = regsub(resp.http.Vary, "^, *", "");
    if (resp.http.Vary == "") {
        remove resp.http.Vary;
    }

    # Sanity check to prevent ever exposing the hash to a client.
    remove resp.http.X-User-Context-Hash;
}
