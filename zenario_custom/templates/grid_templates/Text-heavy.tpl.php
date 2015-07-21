<?php if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); ?>

<script type="text/javascript">
	window.zenarioGrid = {"cols":12,"minWidth":769,"maxWidth":1140,"fluid":true,"responsive":true};
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
			<div class="span span8 span2_3 alpha slot small_slot Main_1">
				<?php slot('Main_1', 'grid'); ?>
			</div>
			<div class="span span4 span1_3 omega slot small_slot Side_1">
				<?php slot('Side_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span8 span2_3 Main_Slots alpha">
				<div class="span span8 span1_1 alpha omega slot small_slot Main_2">
					<?php slot('Main_2', 'grid'); ?>
				</div>
				<div class="clear"></div>
				<div class="span span8 span1_1 alpha omega slot small_slot Main_3">
					<?php slot('Main_3', 'grid'); ?>
				</div>
				<div class="clear"></div>
				<div class="span span8 span1_1 alpha omega slot small_slot Main_4">
					<?php slot('Main_4', 'grid'); ?>
				</div>
			</div>
			<div class="span span4 span1_3 omega slot small_slot Side_2">
				<?php slot('Side_2', 'grid'); ?>
			</div>
		</div>
	</div>
</div>
<div class="Grid_Footer">
	<div class="container container_12">
		<div class="span span12 span1_1 Footer_Area alpha omega">
			<div class="span span12 span1_1 alpha omega slot small_slot Footer">
				<?php slot('Footer', 'grid'); ?>
			</div>
		</div>
	</div>
</div>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php';
}?>


<?php //data:eJytVF1PgzAU_S99JkYm0cmbGjcf3Isz2YMxpKMdNJaW9ENdlv13W8ZYh6uA-gT35J5zz_1INyDFlEoQv2zAB0EqB3E4CkAmCEqWAsM3ECuhcY2kUiYphdLkg6kFHjBEWIAAqHWJDejwtsGRoqRc7bUYLGzyMy-TGV8SipMZZvqgUuUGIMcky5WNC0ipAXbfGHBG121915oVvjEmDOVbdxdeK2GXAafilVdl1L-NnCDsivqndNFp7dVZAdclYVl7QH1XesvR-pcLnWhKk0WV0n-U7eXNIGHe7Y1P1q0oQ9YXnZSZm30MkhmfcD43JDnQeufNHNf0yXRfSS-Z6I_H9sNwuxv9vzuecK76PU0udcfy3p_v8Fu1BjRnsJTbIla6IGxRPzCX1yaEn3UYhtF5AFZUE7QvXRAhuADxClJpQoFlyZkk77gZy12ja39rJSOTTbWyduPobNREj3il7lGG3Ywna_8AVtiE6j3xiFfhTVbD3MHbL6LpGek,//v2// ?>
<?php //checksum:NZyAryP6Isbs1jHkW5qMZOAgczo,// ?>