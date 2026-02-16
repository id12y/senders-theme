<?php
/**
 * FAQ — Admin Views
 *
 * @package SenderSymposium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Main page ─── */

function ss_faq_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab = sanitize_key( $_GET['tab'] ?? 'settings' );
	$url = admin_url( 'themes.php?page=ss-faq' );

	ss_faq_admin_notices();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'FAQ', 'sender-symposium' ); ?></h1>
		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=settings' ); ?>"><?php esc_html_e( 'Settings', 'sender-symposium' ); ?></a>
			<a class="nav-tab <?php echo 'sections' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url . '&tab=sections' ); ?>"><?php esc_html_e( 'Sections & Questions', 'sender-symposium' ); ?></a>
		</nav>
		<div style="padding-top:20px;">
		<?php
		if ( 'sections' === $tab ) {
			$action = sanitize_key( $_GET['action'] ?? '' );
			if ( 'edit' === $action || 'add' === $action ) {
				ss_faq_render_section_editor();
			} else {
				ss_faq_render_sections_list();
			}
		} else {
			ss_faq_render_settings();
		}
		?>
		</div>
	</div>
	<?php
}

/* ─── Notices ─── */

function ss_faq_admin_notices() {
	$msg = sanitize_key( $_GET['message'] ?? '' );
	if ( ! $msg ) {
		return;
	}
	$map = array(
		'settings_saved'  => array( 'success', __( 'Settings saved.', 'sender-symposium' ) ),
		'section_added'   => array( 'success', __( 'Section added.', 'sender-symposium' ) ),
		'section_saved'   => array( 'success', __( 'Section saved.', 'sender-symposium' ) ),
		'section_deleted' => array( 'success', __( 'Section deleted.', 'sender-symposium' ) ),
		'order_saved'     => array( 'success', __( 'Section order saved.', 'sender-symposium' ) ),
		'no_title'        => array( 'error',   __( 'Section title is required.', 'sender-symposium' ) ),
		'not_found'       => array( 'error',   __( 'Section not found.', 'sender-symposium' ) ),
	);
	if ( isset( $map[ $msg ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
	}
}

/* ─── Settings tab ─── */

function ss_faq_render_settings() {
	$faq = ss_get_faq();
	$s   = $faq['settings'];
	?>
	<form method="post">
		<?php wp_nonce_field( 'ss_faq_save_settings' ); ?>
		<input type="hidden" name="ss_faq_action" value="save_settings" />

		<h2><?php esc_html_e( 'Page Copy', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Page Title', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="page_title" value="<?php echo esc_attr( $faq['page_title'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Page Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="page_subtitle" value="<?php echo esc_attr( $faq['page_subtitle'] ); ?>" class="regular-text" /></td></tr>
			<tr><th><label><?php esc_html_e( 'Intro Paragraph', 'sender-symposium' ); ?></label></th>
				<td><textarea name="intro_paragraph" class="large-text" rows="3"><?php echo esc_textarea( $faq['intro_paragraph'] ); ?></textarea></td></tr>
		</table>

		<h2><?php esc_html_e( 'Display Options', 'sender-symposium' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Show Subtitle', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="settings[show_page_subtitle]" value="1" <?php checked( $s['show_page_subtitle'] ); ?> /> <?php esc_html_e( 'Display page subtitle', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Show Intro', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="settings[show_intro_paragraph]" value="1" <?php checked( $s['show_intro_paragraph'] ); ?> /> <?php esc_html_e( 'Display intro paragraph', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Accordion Mode', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="settings[accordion_single_open]" value="1" <?php checked( $s['accordion_single_open'] ); ?> /> <?php esc_html_e( 'Only one question open at a time', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Open First Question', 'sender-symposium' ); ?></th>
				<td><label><input type="checkbox" name="settings[open_first_item_each_section]" value="1" <?php checked( $s['open_first_item_each_section'] ); ?> /> <?php esc_html_e( 'Auto-expand the first question in each section', 'sender-symposium' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Section Style', 'sender-symposium' ); ?></th>
				<td><select name="settings[section_style]">
					<option value="elevated" <?php selected( $s['section_style'], 'elevated' ); ?>><?php esc_html_e( 'Elevated (card surface)', 'sender-symposium' ); ?></option>
					<option value="minimal" <?php selected( $s['section_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal (clean borders)', 'sender-symposium' ); ?></option>
				</select></td></tr>
			<tr><th><?php esc_html_e( 'Hotel Display Style', 'sender-symposium' ); ?></th>
				<td><select name="settings[hotel_display_style]">
					<option value="cards" <?php selected( $s['hotel_display_style'], 'cards' ); ?>><?php esc_html_e( 'Cards (grid)', 'sender-symposium' ); ?></option>
					<option value="list" <?php selected( $s['hotel_display_style'], 'list' ); ?>><?php esc_html_e( 'List (compact rows)', 'sender-symposium' ); ?></option>
				</select></td></tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'sender-symposium' ) ); ?>
	</form>
	<?php
}

/* ─── Sections list ─── */

function ss_faq_render_sections_list() {
	$faq = ss_get_faq();
	$url = admin_url( 'themes.php?page=ss-faq&tab=sections' );
	?>
	<p><a href="<?php echo esc_url( $url . '&action=add' ); ?>" class="button button-primary"><?php esc_html_e( 'Add Section', 'sender-symposium' ); ?></a></p>

	<?php if ( empty( $faq['sections'] ) ) : ?>
		<p><?php esc_html_e( 'No FAQ sections yet. Add one to get started.', 'sender-symposium' ); ?></p>
	<?php else : ?>

		<form method="post">
			<?php wp_nonce_field( 'ss_faq_reorder_sections' ); ?>
			<input type="hidden" name="ss_faq_action" value="reorder_sections" />

			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th style="width:60px;"><?php esc_html_e( 'Order', 'sender-symposium' ); ?></th>
						<th><?php esc_html_e( 'Title', 'sender-symposium' ); ?></th>
						<th><?php esc_html_e( 'Questions', 'sender-symposium' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'sender-symposium' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $faq['sections'] as $sec ) : ?>
					<tr>
						<td><input type="number" name="section_order[<?php echo esc_attr( $sec['id'] ); ?>]" value="<?php echo esc_attr( $sec['order'] ); ?>" min="1" style="width:50px;" /></td>
						<td>
							<strong><?php echo esc_html( $sec['title'] ); ?></strong>
							<?php if ( ! empty( $sec['subtitle'] ) ) : ?>
								<br><span class="description"><?php echo esc_html( $sec['subtitle'] ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo count( $sec['items'] ); ?></td>
						<td>
							<a href="<?php echo esc_url( $url . '&action=edit&id=' . $sec['id'] ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'sender-symposium' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( __( 'Save Order', 'sender-symposium' ), 'secondary' ); ?>
		</form>

	<?php endif;
}

/* ─── Section editor ─── */

function ss_faq_render_section_editor() {
	$faq     = ss_get_faq();
	$sec_id  = sanitize_key( $_GET['id'] ?? '' );
	$section = null;

	if ( $sec_id ) {
		foreach ( $faq['sections'] as $s ) {
			if ( $s['id'] === $sec_id ) {
				$section = $s;
				break;
			}
		}
	}

	$is_new = null === $section;
	if ( $is_new ) {
		$section = array( 'id' => '', 'title' => '', 'subtitle' => '', 'order' => 0, 'items' => array() );
	}

	$url = admin_url( 'themes.php?page=ss-faq&tab=sections' );
	?>
	<p><a href="<?php echo esc_url( $url ); ?>">&larr; <?php esc_html_e( 'Back to Sections', 'sender-symposium' ); ?></a></p>

	<form method="post">
		<?php wp_nonce_field( 'ss_faq_save_section' ); ?>
		<input type="hidden" name="ss_faq_action" value="save_section" />
		<input type="hidden" name="section[id]" value="<?php echo esc_attr( $section['id'] ); ?>" />

		<h2><?php echo $is_new ? esc_html__( 'Add Section', 'sender-symposium' ) : esc_html__( 'Edit Section', 'sender-symposium' ); ?></h2>

		<table class="form-table">
			<tr><th><label><?php esc_html_e( 'Section Title *', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="section[title]" value="<?php echo esc_attr( $section['title'] ); ?>" class="regular-text" required /></td></tr>
			<tr><th><label><?php esc_html_e( 'Section Subtitle', 'sender-symposium' ); ?></label></th>
				<td><input type="text" name="section[subtitle]" value="<?php echo esc_attr( $section['subtitle'] ); ?>" class="regular-text" /></td></tr>
		</table>

		<?php if ( ! $is_new ) : ?>
		<h3><?php esc_html_e( 'Questions', 'sender-symposium' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Check "Delete" and save to remove a question. Empty question fields are ignored. New empty slots at the bottom let you add questions.', 'sender-symposium' ); ?></p>

		<div class="ss-faq-items">
			<?php
			$index = 0;
			foreach ( $section['items'] as $item ) :
				ss_faq_render_item_fields( $item, $index );
				$index++;
			endforeach;

			/* 3 empty slots for new questions */
			for ( $i = 0; $i < 3; $i++ ) :
				ss_faq_render_item_fields( array(
					'id'          => '',
					'question'    => '',
					'answer_html' => '',
					'order'       => $index + 1,
				), $index );
				$index++;
			endfor;
			?>
		</div>
		<?php endif; ?>

		<?php submit_button( $is_new ? __( 'Create Section', 'sender-symposium' ) : __( 'Save Section', 'sender-symposium' ) ); ?>
	</form>

	<?php /* Delete section form */ ?>
	<?php if ( ! $is_new ) : ?>
	<hr />
	<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this section and all its questions?', 'sender-symposium' ) ); ?>');">
		<?php wp_nonce_field( 'ss_faq_delete_section' ); ?>
		<input type="hidden" name="ss_faq_action" value="delete_section" />
		<input type="hidden" name="section_id" value="<?php echo esc_attr( $section['id'] ); ?>" />
		<?php submit_button( __( 'Delete Section', 'sender-symposium' ), 'delete', 'submit', false ); ?>
	</form>
	<?php endif;
}

/* ─── Single item fieldset ─── */

function ss_faq_render_item_fields( $item, $index ) {
	$is_existing = ! empty( $item['id'] );
	?>
	<fieldset class="ss-faq-item-fieldset" style="border:1px solid #ccd0d4;padding:12px 16px;margin-bottom:12px;background:#fff;">
		<legend style="font-weight:600;">
			<?php echo $is_existing ? sprintf( esc_html__( 'Q%d', 'sender-symposium' ), $index + 1 ) : esc_html__( '+ New Question', 'sender-symposium' ); ?>
		</legend>
		<input type="hidden" name="items[<?php echo $index; ?>][id]" value="<?php echo esc_attr( $item['id'] ); ?>" />
		<p>
			<label><strong><?php esc_html_e( 'Question:', 'sender-symposium' ); ?></strong></label><br>
			<input type="text" name="items[<?php echo $index; ?>][question]" value="<?php echo esc_attr( $item['question'] ); ?>" class="large-text" />
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Answer (HTML allowed):', 'sender-symposium' ); ?></strong></label><br>
			<textarea name="items[<?php echo $index; ?>][answer_html]" class="large-text" rows="6"><?php echo esc_textarea( $item['answer_html'] ); ?></textarea>
		</p>
		<?php if ( $is_existing ) : ?>
		<p>
			<label><input type="checkbox" name="delete_items[]" value="<?php echo esc_attr( $item['id'] ); ?>" /> <?php esc_html_e( 'Delete this question', 'sender-symposium' ); ?></label>
		</p>
		<?php endif; ?>
	</fieldset>
	<?php
}
