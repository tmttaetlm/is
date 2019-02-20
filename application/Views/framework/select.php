<select <?php echo $data['dataSet'];?> >
	<?php foreach ($data['items'] as $item=>$value):?>	
	<option value="<?php echo $item;?>" <?php if ($item==$data['selected']) echo 'selected';?> ><?php echo $value;?></option>
	<?php endforeach;?>
</select>