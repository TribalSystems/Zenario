<?php if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed'); ?>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/header.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/header.inc.php';
}?>
<div class="Grid_Top">
	<div class="container container_10">
		<div class="span span4 span2_5 Grouping_Top_Side alpha">
			<div class="span span4 span1_1 alpha omega slot Top_Side_1">
				<?php slot('Top_Side_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span4 span1_1 alpha omega slot Top_Side_2">
				<?php slot('Top_Side_2', 'grid'); ?>
			</div>
		</div>
		<div class="span span6 span3_5 Grouping_Top omega">
			<div class="span span6 span1_1 alpha omega slot Top_1">
				<?php slot('Top_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span6 span1_1 alpha omega slot Top_2">
				<?php slot('Top_2', 'grid'); ?>
			</div>
		</div>
	</div>
</div>
<div class="Grid_Masthead">
	<div class="container container_10">
		<div class="span span10 span1_1 alpha omega slot Masthead_1">
			<?php slot('Masthead_1', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Masthead_2">
			<?php slot('Masthead_2', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Masthead_3">
			<?php slot('Masthead_3', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Masthead_4">
			<?php slot('Masthead_4', 'grid'); ?>
		</div>
	</div>
</div>
<div class="Grid_Main_and_Side">
	<div class="container container_10">
		<div class="span span3 span3_10 Grouping_Side alpha">
			<div class="span span3 span1_1 alpha omega slot Side_1">
				<?php slot('Side_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span3 span1_1 alpha omega slot Side_2">
				<?php slot('Side_2', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span3 span1_1 alpha omega slot Side_3">
				<?php slot('Side_3', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span3 span1_1 alpha omega slot Side_4">
				<?php slot('Side_4', 'grid'); ?>
			</div>
		</div>
		<div class="span span7 span7_10 Grouping_Main omega">
			<div class="span span7 span1_1 alpha omega slot Main_1">
				<?php slot('Main_1', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span7 span1_1 alpha omega slot Main_2">
				<?php slot('Main_2', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span7 span1_1 alpha omega slot Main_3">
				<?php slot('Main_3', 'grid'); ?>
			</div>
			<div class="clear"></div>
			<div class="span span7 span1_1 alpha omega slot Main_4">
				<?php slot('Main_4', 'grid'); ?>
			</div>
		</div>
	</div>
</div>
<div class="Grid_Footer">
	<div class="container container_10">
		<div class="span span10 span1_1 alpha omega slot Footer_1">
			<?php slot('Footer_1', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Footer_2">
			<?php slot('Footer_2', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Footer_3">
			<?php slot('Footer_3', 'grid'); ?>
		</div>
		<div class="clear"></div>
		<div class="span span10 span1_1 alpha omega slot Footer_4">
			<?php slot('Footer_4', 'grid'); ?>
		</div>
	</div>
</div>

<?php if (file_exists(CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php')) {
	require CMS_ROOT. cms_core::$templatePath. '/includes/footer.inc.php';
}?>


<?php //data:eJydlU1vgzAMhv9LzhxGqVqp12nlsl22STtME8pIgGgpQUnYh6r-9yUQGAzilt6wlcd-89oRR5RSzhXavR7RFyO6QLvwJkC5ZCR5lxR_oJ2WNXWZVKkk5ViZ8yi2iWdRoQDpn4qazAA6BX25dYDGmKgrVuYWTZ4YoYafSDCM4kJ3vUt8sPU7Ign_ejbHxt0gcvWffBuob4UNq2382udkb7zNAcV-aKHYJXN7wEoXFJNzw7M1Z9R1OHSvc-jkdpej0fXoGkYv94-VCS5Jt8GgiZFni3zbH81e4dzmAxRgNUABLgPUxGB4abceb6zBc95sPeM14wC8ASjAG4ACvAGohd4sWci9EJrKK59zC1_xmB24_Ck7cPlDduCck6mwq2K5Aytf3DA2NsTfLgxDG2e8ZqSrK6mqRKnYp6mVYa6oxaUUsg_zWpuWe143f5c2uqeZviM5bbKrLvvI8mKcvu012U-nwkZxA7QV41HJFozHBU3y9At154Z6//v2// ?>
<?php //checksum:Idk6kUXPzRFeUY5P_zOUfpjzPcM,// ?>