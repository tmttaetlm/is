<div class="ui-tabs">
    <?php foreach($data['tabItems'] as $key=>$value):?>
    <input type="radio" name="tab" id="<?php echo $key;?>">
        <label for="<?php echo $key;?>"><?php echo $value;?></label>	
    <?php endforeach;?>
    <div class="content">
    <?php foreach($data['tabData'] as $key=>$value):?>
            <article class="<?php echo $key;?>">
                <?php echo $value;?>
                <div class="results">
                </div>    
            </article>
    <?php endforeach;?>
    </div>
</div>
