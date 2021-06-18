/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This function is in a separate file so that you can easily adjust the lookup URL to your needs.
 */
sub user_context_hash_url {
	set req.url = "/_fos_user_context_hash";
}
