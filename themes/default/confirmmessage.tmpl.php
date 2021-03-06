<?php  
/*
 * @author Jacek Materna
 *
 *	One Savant variable: $item which is the processed ouput message content according to lang spec.
 */
 
 global $_base_href;
 
// header
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<?php if(isset($this->hidden_vars)): ?>
	<?php echo $this->hidden_vars; ?>
<?php endif; ?>

<div class="input-form" aria-labelledby="confirm" aria-describedby="confirm">
	<div class="row" id="confirm" role="alert">
		<?php if (is_array($this->item)) : ?>
			<?php foreach($this->item as $e) : ?>
				<p><?php echo htmlspecialchars_decode(stripslashes($e)); ?></p>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="row buttons">
		<input type="submit" name="submit_yes" value="<?php echo $this->button_yes_text; ?>" class="button"/> 
<?php if(!$this->hide_button_no): ?>
		<input type="submit" name="submit_no" value="<?php echo $this->button_no_text; ?>"  class="button"/>
<?php endif; ?>
	</div>
</div>
</form>