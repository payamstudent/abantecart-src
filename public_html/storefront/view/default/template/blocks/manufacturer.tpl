<div class="s_block">
	<div class="block_tl">
		<div class="block_tr">
			<div class="block_tc"><img src="<?php echo $this->templateResource('/image/brands.png'); ?>" alt="" /><?php echo $heading_title; ?></div>
		</div>
	</div>
    <div class="block_cl">
    	<div class="block_cr">
        	<div class="block_cc">
            	<div class="brands"><ul>
                    <?php foreach ($manufacturers as $manufacturer) { ?>
                    <?php if ($manufacturer['manufacturer_id'] == $manufacturer_id) { ?>
                    <li><a href="<?php echo $manufacturer['href']; ?>"><b><?php echo $manufacturer['name']; ?></b></a></li>
                    <?php } else { ?>
                    <li><a href="<?php echo $manufacturer['href']; ?>"><?php echo $manufacturer['name']; ?></a></li>
                    <?php } ?>
                    <?php } ?>
                </ul></div>
            </div>
        </div>
    </div>
	<div class="block_bl">
		<div class="block_br">
			<div class="block_bc">&nbsp;</div>
		</div>
	</div>
</div>