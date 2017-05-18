<div class="wrap">
	<h2><?php echo $form_title ?></h2>
	<form method="post" action="<?php echo $form_action ?>">
		<?php wp_nonce_field(Plance_MTSC_INIT::PAGE.'-form'); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php echo __('Title', 'plance') ?></th>
				<td>
					<input type="text" name="sh_title" value="<?php echo esc_attr($data_ar['sh_title']) ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Code', 'plance') ?></th>
				<td>
					<input type="text" name="sh_code" value="<?php echo esc_attr($data_ar['sh_code']) ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Blocking', 'plance') ?></th>
				<td>
					<select name="sh_is_lock">
						<option value="0"<?php echo selected( 0, $data_ar['sh_is_lock'], false ) ?>><?php echo __('Unlocked', 'plance') ?></option>
						<option value="1"<?php echo selected( 1, $data_ar['sh_is_lock'], false ) ?>><?php echo __('Locked', 'plance') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo __('Description', 'plance') ?></th>
				<td>
					<textarea style="width: 500px; height: 100px;" name="sh_description"><?php echo esc_textarea($data_ar['sh_description']); ?></textarea>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>