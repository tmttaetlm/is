<input type="tel"
	   name=<?php echo $data['name'];?>
	   data-id=<?php echo $data['id'];?> 
	   value="<?php 
	   			if (!empty($data['value'])) {
					echo "+".substr($data['value'],0,1)." (".substr($data['value'],1,3).") ".substr($data['value'],4,3)." ".substr($data['value'],7);
				}
			  ?>"
	   placeholder="+7 (___) ___ __ __"
	   maxlength="17"
	   autocomplete="off"
>