<?php
/**
 * Plugin Name: Baichdo Notify
 * Description: Simple menu item to send a push notification to every registered app device (reads RTCL's existing push token table).
 * Version: 1.1.0
 * Author: Ahmed Faran
 * Text Domain: baichdo-notify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BN_EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';
const BN_BATCH_SIZE    = 100;

add_action( 'admin_menu', 'bn_add_menu' );
function bn_add_menu(): void {
	add_menu_page(
		__( 'Send Notification', 'baichdo-notify' ),
		__( 'Send Notification', 'baichdo-notify' ),
		'manage_options',
		'baichdo-notify',
		'bn_render_page',
		'dashicons-megaphone',
		27
	);
}

function bn_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'baichdo-notify' ) );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'rtcl_push_notifications';

	$result = null;

	if ( isset( $_POST['bn_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bn_nonce'] ) ), 'bn_send' ) ) {
		$title = sanitize_text_field( wp_unslash( $_POST['bn_title'] ?? '' ) );
		$body  = sanitize_textarea_field( wp_unslash( $_POST['bn_body'] ?? '' ) );
		$url   = esc_url_raw( wp_unslash( $_POST['bn_url'] ?? '' ) );

		if ( '' === $title || '' === $body ) {
			$result = array(
				'type'    => 'error',
				'message' => __( 'Title and message are both required.', 'baichdo-notify' ),
			);
		} else {
			// All tokens: logged-in users AND guest devices, unlike RTCL's own
			// broadcast which only reaches user_id IS NULL (guest) devices.
			$tokens = $wpdb->get_col( "SELECT push_token FROM {$table}" );

			if ( empty( $tokens ) ) {
				$result = array(
					'type'    => 'warning',
					'message' => __( 'No registered devices found.', 'baichdo-notify' ),
				);
			} else {
				$sent    = 0;
				$errors  = 0;
				$log     = array();
				$removed = 0;

				foreach ( array_chunk( $tokens, BN_BATCH_SIZE ) as $batch ) {
					$messages = array();
					foreach ( $batch as $token ) {
						$message = array(
							'to'    => $token,
							'title' => $title,
							'body'  => $body,
							'sound' => 'default',
						);
						if ( ! empty( $url ) ) {
							$message['data'] = array( 'url' => $url );
						}
						$messages[] = $message;
					}

					$response = wp_remote_post(
						BN_EXPO_PUSH_URL,
						array(
							'headers' => array(
								'Content-Type' => 'application/json',
								'Accept'       => 'application/json',
							),
							'body'    => wp_json_encode( $messages ),
							'timeout' => 20,
						)
					);

					if ( is_wp_error( $response ) ) {
						$errors += count( $batch );
						$log[]   = $response->get_error_message();
						continue;
					}

					$code = (int) wp_remote_retrieve_response_code( $response );
					$data = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( 200 !== $code ) {
						$errors += count( $batch );
						$log[]   = sprintf( 'Expo API returned HTTP %d', $code );
						continue;
					}

					if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
						foreach ( $data['data'] as $i => $ticket ) {
							if ( isset( $ticket['status'] ) && 'ok' === $ticket['status'] ) {
								$sent++;
							} else {
								$errors++;
								if ( isset( $ticket['message'] ) ) {
									$log[] = $ticket['message'];
								}

								// Dead token. Delete it so count never over-reports.
								$dead_error = $ticket['details']['error'] ?? '';
								if ( 'DeviceNotRegistered' === $dead_error && isset( $batch[ $i ] ) ) {
									$wpdb->delete( $table, array( 'push_token' => $batch[ $i ] ) );
									$removed++;
								}
							}
						}
					} else {
						$sent += count( $batch );
					}
				}

				$message = sprintf(
					/* translators: 1: sent count, 2: error count */
					__( 'Sent to %1$d device(s). %2$d failed.', 'baichdo-notify' ),
					$sent,
					$errors
				);
				if ( $removed > 0 ) {
					$message .= ' ' . sprintf(
						/* translators: %d: removed dead token count */
						__( '%d dead token(s) removed.', 'baichdo-notify' ),
						$removed
					);
				}

				$result = array(
					'type'    => $errors > 0 ? 'warning' : 'success',
					'message' => $message,
					'log'     => $log,
				);
			}
		}
	}

	$device_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Send Push Notification', 'baichdo-notify' ); ?></h1>

		<p>
			<?php
			printf(
				/* translators: %d: number of registered devices */
				esc_html__( '%d device(s) currently registered (logged-in + guest).', 'baichdo-notify' ),
				$device_count
			);
			?>
		</p>

		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo esc_attr( $result['type'] ); ?> is-dismissible">
				<p><?php echo esc_html( $result['message'] ); ?></p>
				<?php if ( ! empty( $result['log'] ) ) : ?>
					<ul style="margin-left:1em;list-style:disc;">
						<?php foreach ( array_slice( $result['log'], 0, 10 ) as $line ) : ?>
							<li><?php echo esc_html( $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'bn_send', 'bn_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="bn_title"><?php esc_html_e( 'Title', 'baichdo-notify' ); ?></label></th>
					<td><input type="text" id="bn_title" name="bn_title" class="regular-text" maxlength="65" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="bn_body"><?php esc_html_e( 'Message', 'baichdo-notify' ); ?></label></th>
					<td><textarea id="bn_body" name="bn_body" class="large-text" rows="4" maxlength="240" required></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="bn_url"><?php esc_html_e( 'Link (optional)', 'baichdo-notify' ); ?></label></th>
					<td>
						<input type="url" id="bn_url" name="bn_url" class="regular-text" placeholder="baichdo://listings/123" />
						<p class="description"><?php esc_html_e( 'Opened in the app when tapped. Leave blank for none.', 'baichdo-notify' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Send Notification', 'baichdo-notify' ) ); ?>
		</form>
	</div>
	<?php
}