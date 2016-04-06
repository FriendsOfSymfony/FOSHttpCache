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
    if (resp.http.Cache-Debug) {
        if (obj.hits > 0) {
            set resp.http.Cache = "HIT";
        } else {
            set resp.http.Cache = "MISS";
        }
    }
}
