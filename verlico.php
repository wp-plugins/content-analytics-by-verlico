<?php
/*
Plugin Name: Verlico Analytics
Description: Find out how many people actually reading your blog posts and how long they stay. This plugin Adds Verlico content analytics to WordPress.
Version: 1.0.0
Author: Verlico
Author URI: http://www.verlico.com/
*/

/*
Copyright 2009-2013 Verlico Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

function verlico_menu() {
	add_options_page( 'verlico plugin options', 'Verlico', 'manage_options', 'verlico-options', 'verlico_options_page' );
	add_menu_page( 'Verlico Console', 'Verlico', 'edit_posts', 'verlico_console', 'verlico_console', 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeD0iMCIgeT0iMCIgdmlld0JveD0iMCAwIDYwMCA2MDAiIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTEzMy45IDIyMS4zTDY5LjEgOTUuMUM2Mi42IDgyLjIgNzEuOSA2NyA4Ni40IDY3aDEyNi44YzE0LjMgMCAyMy43IDE1IDE3LjQgMjcuOGwtNjIgMTI2LjJDMTYxLjUgMjM1LjMgMTQxLjEgMjM1LjQgMTMzLjkgMjIxLjN6TTIzMC41IDQxNy4zbDUwLjQgMTAwLjZjMTAuMiAyMC40IDM5LjUgMjAgNDkuMi0wLjVMNTMxLjIgOTRjNi0xMi42LTMuNC0yNy4yLTE3LjQtMjdsLTExMS4xIDBjLTEwLjYgMC0yMC4zIDYuMS0yNC44IDE1LjZMMjMwLjIgMzkzLjVDMjI2LjYgNDAxIDIyNi43IDQwOS44IDIzMC41IDQxNy4zeiIvPjwvc3ZnPg==' );
}

function verlico_console() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$domain     = apply_filters( 'verlico_config_domain', verlico_get_display_url( get_option( 'home' ) ) );
	$iframe_url = add_query_arg( array(
		'url' => verlico_get_display_url( $domain )
	), '//www.verlico.com/insight/' );
	?>
	<iframe id="verlico-iframe" style="padding:10px 0;min-height: 640px" width="100%" height="100%"
	        src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
<?php
}

function verlico_options_page() {
	$domain = apply_filters( 'verlico_config_domain', verlico_get_display_url( get_option( 'home' ) ) );
	?>
	<div class="wrap">
		<h2>Verlico</h2>

		<form method="post" action="options.php">
			<?php
			// outputs all of the hidden fields that options.php will check, including the nonce
			wp_nonce_field( 'update-options' );
			settings_fields( 'verlico-options' );
			$verlico_username =get_option( 'verlico_username' );
			if ( empty( $verlico_username ) ) { ?>
				<br/>
				If you do not have your verlico account yet ,
				<a href="http://www.verlico.com/join" target="_blank">signup here</a>
				<br/>
			<?php } ?>
			<table class="form-table">
				<tr>
					<th scope="row">Verlico Username</th>
					<td><input size="30" type="text" name="verlico_username"
					           value="<?php echo esc_attr( get_option( 'verlico_username' ) ); ?>"/>
					</td>
				</tr>
				<tr>
					<th scope="row">Verlico User ID</th>
					<td><input size="30" type="text" disabled="disabled" name="verlico_userId"
					           value="<?php echo esc_attr( get_option( 'verlico_userId' ) ); ?>"/>
					</td>
				</tr>
				<input size="30" hidden="hidden" type="text" name="verlico_domain"
				       value="<?php echo $domain ?>"/>
			</table>
			<br/>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>"/>
			</p>
		</form>
	</div>
<?php
}

// Function to register settings and sanitize output. To be called later in add_action
function verlico_register_settings() {
	register_setting( 'verlico-options', 'verlico_username', 'verlico_get_id' );
}

function verlico_get_id( $username ) {
	if ( $username ) {
		$response     = wp_remote_get( 'http://www.verlico.com/api/user/getId/' . $username );
		$body         = wp_remote_retrieve_body( $response );
		$responseCode = wp_remote_retrieve_response_code( $response );
		if ( $responseCode == 404 ) {
			add_settings_error( 'verlico_username', 'invalid_username', 'This username does not exists. Visit www.verlico.com/join to signup', 'error' );
		} else {
			try {
				$json = json_decode( $body );
				update_option( 'verlico_userId', $json->id );
			} catch ( Exception $ex ) {
				add_settings_error( 'verlico_username', 'invalid_username', 'This username does not exists. Visit www.verlico.com/join to signup', 'error' );
			}
		}
	} else {
		update_option( 'verlico_userId', 0 );
	}

	return $username;
}

function verlico_add_head() {
	$user_id = get_option( 'verlico_userid' );
	if ( $user_id ) {
		?>
		<script charset="utf-8" type="text/javascript">
			window._verId = <?php echo intval( $user_id ); ?>;
			setTimeout(function () {
				(function (d, t) {
					var s = d.createElement(t),
						x = d.getElementsByTagName(t)[0];
					s.type = 'text/javascript';
					s.async = true;
					s.src = '//s3-us-west-2.amazonaws.com/verlico-static/assets/newEmbed/embed.min.js';
					x.parentNode.insertBefore(s, x);
				})(document, 'script');
			}, 0);
		</script>

		<?php

		if ( is_single() ) {
			$post   = get_queried_object();
			$domain = apply_filters( 'verlico_config_domain', verlico_get_display_url( get_option( 'home' ) ) );

			// Use the author's display name
			$author = get_the_author_meta( 'display_name', $post->post_author );
			$author = apply_filters( 'verlico_config_author', $author );

			$title = apply_filters( 'verlico_config_title', get_the_title() );

			$published = get_the_time( 'U' ) * 1000;

			$cats = get_the_terms( $post->ID, 'category' );
			$cat_names = '';
			if ( $cats ) {
				$cat_names = array();
				foreach ( $cats as $cat ) {
					$cat_names[] = $cat->name;
				}
				$cat_names = (array) apply_filters( 'verlico_config_sections', $cat_names );
				if ( count( $cat_names ) ) {
					foreach ( $cat_names as $index => $name ) {
						$cat_names[ $index ] = esc_js( $name );
					}
				}
			}

			$categories = esc_js( implode( ',', $cat_names ) );
			$value      = array( 'domain'     => esc_js( $domain ),
			                     'author'     => esc_js( $author ),
			                     'title'      => esc_js( $title ),
			                     'published'  => $published,
			                     'categories' => $categories
			);
			printf( "<meta id='verlico_data' name='verlico' content='%s'>", esc_attr( json_encode( $value ) ) );
		}
		?>
	<?php
	}
}

function verlico_get_display_url( $url ) {
	return strtok( preg_replace( "/(https?:\/\/)?(www\.)?/i", "", $url ), "/" );
}

// Add settings link on plugin page
function verlico_plugin_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=verlico-options">Settings</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

// Add verlico link on plugin page
function verlico_plugin_verlico_link( $links ) {
	$settings_link = '<a href="admin.php?page=verlico_console">Verlico</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'admin_menu', 'verlico_menu' );
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'verlico_plugin_settings_link' );
add_filter( "plugin_action_links_$plugin", 'verlico_plugin_verlico_link' );

// If admin register settings on page that have been saved
// if not, add script to wp_head
if ( is_admin() ) {
	add_action( 'admin_init', 'verlico_register_settings' );
} else {
	add_action( 'wp_head', 'verlico_add_head' );
}
?>
