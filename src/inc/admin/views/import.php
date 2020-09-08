<?php
/**
 * Import page for the admin.
 */

?>

<div class="wrap">
	<h1>
		<?php esc_html_e( 'Import Article from Kafkai', 'kafkai-wp' ); ?>
	</h1>

	<form method="post"> 
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="niche">Niche</label>
					</th>
					<td>
						<select id="niche" name="niche">
							<option value="0">Sunday</option>
							<option selected="selected" value="1">Monday</option>
							<option value="2">Tuesday</option>
							<option value="3">Wednesday</option>
							<option value="4">Thursday</option>
							<option value="5">Friday</option>
							<option value="6">Saturday</option>
						</select>
					</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Import Articles', 'kafkai-wp' ); ?>" class="button-primary">
		</p>
	</form>
</div>
