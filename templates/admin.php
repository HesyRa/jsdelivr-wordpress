<?php
/**
 * Admin page template
 *
 * @package JsDelivrCdn.
 */

$ajax_nonce = wp_create_nonce( JSDELIVRCDN_PLUGIN_NAME );

$options = get_option( JsDelivrCdn::PLUGIN_SETTINGS );

$jsdelivr_table = new JsdelivrTable();
$jsdelivr_table->prepare_items();

?>
<div class="wrap">
	<h1 class="jsdelivrcdn-main-settings-header">jsDelivr CDN Settings</h1>
	<div class="jsdelivrcdn-top-section">
		<div class="jsdelivrcdn-settings-wrapper">
			<h2 class="jsdelivrcdn-setttings-options-title"><?php echo esc_attr( __( 'Settings' ) ); ?></h2>
			<form id="jsdelivrcdn_settings_form" class="jsdelivrcdn-settings-form" method="post" data-ajaxnonce="<?php echo esc_attr( $ajax_nonce ); ?>">
				<?php
				settings_fields( JsDelivrCdn::PLUGIN_SETTINGS );
				do_settings_sections( 'main_settings' );
				?>
			</form>
		</div>
		<div class="jsdelivrcdn-description-wrapper">
			<h2><?php echo esc_attr( __( 'About' ) ); ?> jsDelivr</h2>
			<p class="description">
				jsDelivr CDN is free CDN for open source files that utilizes load-balancing amon multiple CDN providers
				such as Stackpath, Cloudflare, Fastly and Quantil in China. It results in after website an lower
				bandwidth usage for you. Learn more about it here
				<a href="https://www.jsdelivr.com/network/infographic" target="_black">https://www.jsdelivr.com/network/infographic</a>
			</p>
			<p class="description">
				This simple plugin allow you to get full benefits of the jsDelivr CDN without any hassle. And remember
				thatjsDelivr  can only support open source files and will not work for your personal images and other commercial files.
			</p>
			<p class="description">
				The table below shows all the files the plugin was able to find and replace with jsDelivr powered URLs for better performance.
			</p>
		</div>
	</div>
	<div class="jsdelivrcdn-table-wrapper">
		<form id="bulk-action-form" method="post">
			<?php $jsdelivr_table->display(); ?>
			<div class="buttons-wrapper">
				<?php if ( $options[ JsDelivrCdn::ADVANCED_MODE ] ) { ?>
					<button id="delete_source_list" class="button button-primary"><span class="dashicons dashicons-update spin hidden"></span> Delete All</button>
					<button id="clear_source_list" class="button button-primary"><span class="dashicons dashicons-update spin hidden"></span> Clear All</button>
				<?php } ?>
				<button id="jsdelivr_analyze" class="button button-primary" ><span class="dashicons dashicons-update spin hidden"></span> Analyze</button>
				<button type="submit" name="submit" id="submit" class="button button-primary" style="float:right" ><span class="dashicons dashicons-update spin hidden"></span> Save Active</button>
			</div>
		</form>
	</div>
</div>
<?php
