<?php
/**
 * Holding Page partial — Sender Symposium (post-event)
 *
 * Renders the editable "watch this space" holding page from saved homepage
 * settings (ss_get_homepage()). All copy is admin-editable via
 * Sender Symposium → Settings → Homepage → Holding Page; nothing is hardcoded.
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hp = ss_get_homepage();

$heading      = $hp['holding_heading'] ?? '';
$body         = $hp['holding_body'] ?? '';
$cta_text     = $hp['holding_cta_text'] ?? '';
$cta_url      = $hp['holding_cta_url'] ?? '';
$event_line   = $hp['holding_event_line'] ?? '';
$form_enabled = ! empty( $hp['holding_form_enabled'] );
$form_prompt  = $hp['holding_form_prompt'] ?? '';
$form_button  = $hp['holding_form_button'] ?? '';
$msg_success  = $hp['holding_form_success'] ?? '';
$msg_error    = $hp['holding_form_error'] ?? '';

/* Split body into paragraphs on blank lines. */
$paragraphs = preg_split( '/\n\s*\n/', trim( (string) $body ) );

/* Signup feedback from the POST → redirect → GET flow. */
$signup_status = isset( $_GET['ss_signup'] ) ? sanitize_key( wp_unslash( $_GET['ss_signup'] ) ) : '';
$is_success    = ( 'success' === $signup_status );
$is_invalid    = ( 'invalid' === $signup_status );
$is_error      = ( 'error' === $signup_status );
?>

<section class="section ss-holding" aria-labelledby="ss-holding-heading">
	<div class="container ss-holding__inner">

		<h1 id="ss-holding-heading" class="ss-holding__heading"><?php echo esc_html( $heading ); ?></h1>

		<?php if ( ! empty( $paragraphs ) ) : ?>
		<div class="ss-holding__body flow">
			<?php foreach ( $paragraphs as $para ) : ?>
				<?php $para = trim( $para ); ?>
				<?php if ( '' !== $para ) : ?>
					<p><?php echo esc_html( $para ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( '' !== $cta_text && '' !== $cta_url ) : ?>
		<div class="ss-holding__cta">
			<a class="btn btn--primary ss-holding__cta-btn" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( $cta_text ); ?>
			</a>
			<?php if ( '' !== $event_line ) : ?>
				<p class="ss-holding__event-line text-small"><?php echo esc_html( $event_line ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $form_enabled ) : ?>
		<div class="ss-holding__notify" id="ss-notify">
			<form
				class="ss-holding__form"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				aria-labelledby="ss-notify-prompt"
			>
				<?php if ( '' !== $form_prompt ) : ?>
					<h2 id="ss-notify-prompt" class="ss-holding__form-prompt"><?php echo esc_html( $form_prompt ); ?></h2>
				<?php endif; ?>

				<?php if ( $is_success ) : ?>
					<p id="ss-notify-status" class="ss-holding__status ss-holding__status--success" role="status">
						<?php echo esc_html( $msg_success ); ?>
					</p>
				<?php elseif ( $is_invalid ) : ?>
					<p id="ss-notify-status" class="ss-holding__status ss-holding__status--error" role="alert">
						<?php echo esc_html( $msg_error ); ?>
					</p>
				<?php elseif ( $is_error ) : ?>
					<p id="ss-notify-status" class="ss-holding__status ss-holding__status--error" role="alert">
						<?php esc_html_e( 'Sorry, something went wrong. Please try again.', 'sender-symposium' ); ?>
					</p>
				<?php endif; ?>

				<div class="ss-holding__field">
					<label for="ss-holding-email"><?php esc_html_e( 'Email address', 'sender-symposium' ); ?></label>
					<input
						type="email"
						id="ss-holding-email"
						name="ss_email"
						value=""
						required
						autocomplete="email"
						inputmode="email"
						spellcheck="false"
						<?php if ( $is_invalid ) : ?>aria-invalid="true" aria-describedby="ss-notify-status"<?php endif; ?>
						placeholder="<?php esc_attr_e( 'you@company.com', 'sender-symposium' ); ?>"
					/>
				</div>

				<?php /* Honeypot — hidden from users and assistive tech; bots that fill it are silently ignored. */ ?>
				<div class="ss-holding__hp" aria-hidden="true">
					<label for="ss-holding-website"><?php esc_html_e( 'Leave this field empty', 'sender-symposium' ); ?></label>
					<input type="text" id="ss-holding-website" name="ss_website" value="" tabindex="-1" autocomplete="off" />
				</div>

				<input type="hidden" name="action" value="ss_holding_signup" />
				<?php wp_nonce_field( 'ss_holding_signup', 'ss_holding_nonce' ); ?>

				<button type="submit" class="btn btn--primary ss-holding__submit">
					<?php echo esc_html( '' !== $form_button ? $form_button : __( 'Notify me', 'sender-symposium' ) ); ?>
				</button>
			</form>
		</div>
		<?php endif; ?>

	</div>
</section>
