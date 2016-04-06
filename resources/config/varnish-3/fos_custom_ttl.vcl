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
 */
sub fos_custom_ttl_fetch {
    if (beresp.http.X-Reverse-Proxy-TTL) {
        /*
         * Note that there is a ``beresp.ttl`` field in VCL but unfortunately
         * it can only be set to absolute values and not dynamically. Thus we
         * have to resort to an inline C code fragment.
         */
        C{
            char *ttl;
            ttl = VRT_GetHdr(sp, HDR_BERESP, "\024X-Reverse-Proxy-TTL:");
            VRT_l_beresp_ttl(sp, atoi(ttl));
        }C

        unset beresp.http.X-Reverse-Proxy-TTL;
    }
}
