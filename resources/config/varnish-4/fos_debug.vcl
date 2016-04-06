/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

sub fos_debug_deliver {
    # Add extra headers if debugging is enabled
    # In Varnish 4 the obj.hits counter behaviour has changed, so we use a
    # different method: if Varnish contains only 1 id, we have a miss, if it
    # contains more (and therefore a space), we have a hit.
    if (resp.http.Cache-Debug) {
        if (resp.http.Varnish ~ " ") {
            set resp.http.Cache = "HIT";
        } else {
            set resp.http.Cache = "MISS";
        }
    }
}
