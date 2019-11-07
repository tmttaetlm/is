<input type="checkbox" 
	   name=<?php echo $data['name'];?>
	   class=<?php echo $data['class'];?>
	   value=<?php echo $data['id'];?>
	   <?php echo $data['checked'];?>
	   <?php if (isset($data['disabled'])) {echo $data['disabled'];}?>
	   style="margin-left: 46%;"
>