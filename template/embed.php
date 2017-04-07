<aside <?php opengraph_embed_classes() ?>>
	<div class="thumbnail">
		<?php opengraph_the_thumnbnail(); ?>
	</div>
	<p class="title"><a href="<?php opengraph_the_permalink(); ?>"><?php opengraph_the_title(); ?></a></p>
	<p><?php opengraph_the_content(); ?></p>
	<div class="site">
		<?php opengraph_the_site_name(); ?>
	</div>
</aside>
