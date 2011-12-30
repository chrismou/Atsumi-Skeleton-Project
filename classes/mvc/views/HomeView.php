<?php
class HomeView extends TemplateView {
	
	protected function content() {
		?>
		
		<h1><?=$this->get_title;?></h1>
		
		<?php

		pfl('%s', $this->get_data);
	}
}
?>
