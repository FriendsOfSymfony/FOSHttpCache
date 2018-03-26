/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Read a custom TTL header for the time to live information, to be used
 * instead of s-maxage.
 *
 * This needs an `import std;` in your main VCL. If you do not already import
 * the standard vmod, you need to add it there.
 */
sub fos_custom_ttl_backend_response {
    if (beresp.http.X-Reverse-Proxy-TTL) {
        set beresp.ttl = std.duration(beresp.http.X-Reverse-Proxy-TTL + "s", 0s);
        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}
