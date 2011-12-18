<?php
abstract class TemplateView extends mvc_HtmlView {
	protected function getTitle() {
		return $this->get_siteName;
	}
	
	protected function renderDoctype() {
		pfl('<!DOCTYPE html>');
	}
	
	protected function renderHtml() {
		pfl('<html lang="en">');
		$this->renderHead();
		$this->renderBody();
		pfl('</html>');
	}
	
	protected function getHtmlTitle() {
		$a = "";
		if ($this->get_htmlTitle)
			$a .= $this->get_htmlTitle;
		else if ($this->get_title)
			$a = $this->get_title;
		$a .= sf('%h%h', ($a) ? " | " : "", $this->get_siteName);
		return $a;
	}
	
	protected function renderHeadContent() {
		pfl('<title>%s</title>', $this->getHtmlTitle());
		$this->renderHeadMeta();
		$this->renderHeadCss();
		$this->renderHeadJs();
	}

	protected function renderHeadCss() {
		pfl('<link type="text/css" rel="stylesheet" href="/css/main.css" />');
	}

	protected function renderHeadJs() {
		pfl('
		<script type="text/javascript" src="/js/main.js"></script>
		
		<!--[if IE]>
		<script type="text/javascript" src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->'
		);
	}

	protected function renderBodyContent() {
		?>
		
		<div class="wrapper">
			<div class="header">
				<div class="header-inner">
					<h1 class="site-title"><?php echo $this->get_siteName; ?></h1>
				</div>
			</div>
			
			<div class="content">
				<div class="content-inner">
					<?php $this->content(); ?>
				</div>
			</div>
			
			<div class="footer">
				<div class="footer-inner"></div>
			</div>
		</div>
		
		<?php
	}
	
	abstract protected function content();
}
?>
