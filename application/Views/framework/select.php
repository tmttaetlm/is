<select <?php array_key_exists('name', $data) ? print('name="'.$data['name'].'"') : print("");
			array_key_exists('id', $data) ? print('id="'.$data['id'].'"') : print(""); 
			array_key_exists('data-id', $data) ? print('data-id="'.$data['data-id'].'"') : print(""); 
			array_key_exists('size', $data) ? print('size="'.$data['size'].'"') : print(""); 
		?>>
	<?php foreach ($data['items'] as $item=>$value):?>	
	<option value="<?php if (is_array($value)) { array_key_exists('item', $value) ? print($value['item']) : print(''); } else { print($value); } ?>"
			data-oid="<?php if (is_array($value)) { array_key_exists('oid', $value) ? print($value['oid']) : print(''); } else { print(''); } ?>"
		<?php if (is_array($value)) {
				  if (array_key_exists('selected', $data)) {
					  if ($value['item']==$data['selected']) { echo 'selected'; }
				  }
			  } else {
				    if ($value==$data['selected']) { echo 'selected'; }
			  };
			  array_key_exists('data-oid', $data['items']) ? print('data-oid="'.$data['data-oid'].'"') : print(""); ?>>
			<?php if (is_array($value)) { array_key_exists('item', $value) ? print($value['item']) : print(''); } else { print($value); } ?>
	</option>
	<?php endforeach;?>
</select>