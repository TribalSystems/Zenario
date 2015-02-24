<?php if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); ?>

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
			<div class="span span12 span1_1 alpha omega slot small_slot Footer">
				<?php slot('Footer', 'grid'); ?>
			</div>
		</div>
	</div>
</div>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php';
}?>


<?php //data:eJy9lE1vgzAMhv9LzmgaLdoHt3Vau8N6mSb1ME0oJS5ECwlKwtaq6n9fQilNWSPoDjuBX_l97NiBLUqBMYXi9y36pkTnKA5HAcokJclSAv5EsZYVNEqqVJIyrEw-mlnhGTABiQKkNyUY0fHtghOiYkIfWBwXNvlNlMlcLCmDZA68OlLq3ADlQLNc27jAjBlh_4yR4GzT5butWfCDacJYfp1u7G0l7GvAqXjrpYyGHyOnBFyof0rj3tY-nBWIqqQ86w5o6Eongmz-uNBpxViyqFOGj7K7vDmm3Lu9u7N1reWCitFZyESsL7oCfkrvFRhE6d_5EEr0jzdnKoQe9jNwrXuXd-O-q9apdcHhjJYKW8SiC8oXzSd9c29CvG7CMIyuA7RiFSWH0gWVUkgUrzBTJpSgSsEV_YJ2LI8t1742JIPJZpW27cbR1aiNXmCln0gGbsarbf8o1tqUVQfjia_W26zWuZd3P0Nq6xI,//v2// ?>
<?php //checksum:nEwN9tHSmcOqaRikAjJB-386KqA,// ?>