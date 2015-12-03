<?php if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); ?>

<script type="text/javascript">
	zenarioGrid = {"cols":12,"minWidth":769,"maxWidth":1140,"fluid":true,"responsive":true};
</script>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/header.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/header.inc.php';
}?>
<div class="Grid_Header">
	<div class="container container_12">
		<div class="span span12 span1_1 alpha omega responsive_only slot small_slot Top_Mobile_Menu">
			<?php slot('Top_Mobile_Menu', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span12 span1_1 Top_Area alpha omega">
			<div class="span span3 span1_4 alpha slot small_slot Top_1">
				<?php slot('Top_1', 'grid'); ?>
			</div>
			<div class="span span7 span7_12 responsive slot small_slot Top_2">
				<?php slot('Top_2', 'grid'); ?>
			</div>
			<div class="span span2 span1_6 omega slot small_slot Top_3">
				<?php slot('Top_3', 'grid'); ?>
			</div>
		</div>
	</div>
</div>
<div class="Grid_Body">
	<div class="container container_12">
		<div class="span span12 span1_1 alpha omega slot small_slot Full_Width">
			<?php slot('Full_Width', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span12 span1_1 Main_Area alpha omega">
			<div class="span span8 span2_3 alpha slot small_slot Main">
				<?php slot('Main', 'grid'); ?>
			</div>
			<div class="span span4 span1_3 omega slot small_slot Box_1">
				<?php slot('Box_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span4 span1_3 alpha slot small_slot Box_2">
				<?php slot('Box_2', 'grid'); ?>
			</div>
			<div class="span span4 span1_3 slot small_slot Box_3">
				<?php slot('Box_3', 'grid'); ?>
			</div>
			<div class="span span4 span1_3 omega slot small_slot Box_4">
				<?php slot('Box_4', 'grid'); ?>
			</div>
		</div>
	</div>
</div>
<div class="Grid_Footer">
	<div class="container container_12">
		<div class="span span12 span1_1 Footer_Area alpha omega">
			<div class="span span2 span1_6 Space_In_Footer alpha responsive space">
				<span class="pad_slot">&nbsp;</span>
			</div>
			<div class="span span8 span2_3 slot small_slot Footer">
				<?php slot('Footer', 'grid'); ?>
			</div>
			<div class="span span2 span1_6 omega slot small_slot Built_On">
				<?php slot('Built_On', 'grid'); ?>
			</div>
		</div>
	</div>
</div>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php';
}?>


<?php //data:eJy9VMtOwzAQ_BefI0TaiEduFNGCRIUESD0gFLmxm1g4duQHtKr679h51W0TJeXAKdnJzux6duMtiDGlEoQfW_BDkEpB6I88kAiCoqXA8AuESmhcIbGUUUyhNPlgZoFHDBEWwANqk2MDOrydd6AoKVe1FoOZTX7neTTnS0JxNMdM71WKXA-kmCSpsnEGKTVA-QwBZ3RzrO-2ZoXvTBOGcnK6cWcrfl8DTsXrTpXR8GOkBGFXtNulcW9rn84IuM4JS44NGjrSCUebPw50qimNFkXKcCuPhzeHhHVO76a1rqWcUTFoFZnw9Vkr0K3SuwKDVPpnPkQl-MfNmXKuhl0GLrVkdU7cbloOY1yXdqlv9kP0xE4ql4zuP619jU5U-mxv_wsmmlAVvfRvZJvzBou5dcDalBG2qO6bq1sTwnUV-n5w6YEV1QTVxTMiBBcgXEEqTSiwzDmT5LsxLrlvdO1rpWRkkplW9txhcDFqome8Ug8owW7Gq21_DxbYlOqaeMAr8CarYZbw7hfTlR72//v2// ?>
<?php //checksum:XRsslYJvSDMnrIpuTM6tpHKchQw,// ?>