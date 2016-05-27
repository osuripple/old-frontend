<?php theme_include('header'); ?>
		<section class="content wrap" id="article-<?php echo article_id(); ?>">
			<h1><?php echo article_title(); ?></h1>

			<article>
				<?php echo article_markdown(); ?>
			</article>

			<section class="footnote">
				<!-- Unfortunately, CSS means everything's got to be inline. -->
				<p>This article is my <?php echo numeral(article_number(article_id()), true); ?> oldest. It is <?php echo count_words(article_markdown()); ?> words long. <?php echo article_custom_field('attribution'); ?></p>
			</section>
		</section>

		<section class="comments">
			<div id="disqus_thread" style="max-width: 750px; margin-left: auto; margin-right: auto;"></div>
			<script>
			var disqus_config = function () {
				this.page.url = '<?php echo full_url().current_url(); ?>';
				this.page.identifier = 'blog_article_<?php echo article_id(); ?>';
				this.page.title = '<?php echo page_title(); ?>';
			};
			(function() { // DON'T EDIT BELOW THIS LINE
			var d = document, s = d.createElement('script');

			s.src = '//rppl.disqus.com/embed.js';

			s.setAttribute('data-timestamp', +new Date());
			(d.head || d.body).appendChild(s);
			})();
			</script>
			<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript" rel="nofollow">comments powered by Disqus.</a></noscript>
		</section>

<?php theme_include('footer'); ?>
