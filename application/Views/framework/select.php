<select name = <?php echo $data['name'];?> data-id=<?php echo $data['id'];?> >
	<?php foreach ($data['items'] as $item=>$value):?>	
	<option value="<?php echo $value;?>" <?php if ($item==$data['selected']) echo 'selected';?> ><?php echo $value;?></option>
	<?php endforeach;?>
</select>