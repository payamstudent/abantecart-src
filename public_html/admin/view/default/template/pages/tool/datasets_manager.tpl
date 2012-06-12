<div id="aPopup">

	<div class="popbox_tl">
		<div class="popbox_tr">
			<div class="popbox_tc"></div>
		</div>
	</div>
	<div class="message_head" >
		<div id="popup_title" class="message_title"><?php echo $popup_title;?></div>
	</div>
	<div class="popbox_cl"><div class="popbox_cr"><div class="popbox_cc message_body" >
		<div class="aform">
			<div class="afield mask2">
				<div class="tl"><div class="tr"><div class="tc"></div></div></div>
				<div class="cl"><div class="cr"><div class="cc">
					<div class="message_text">
						<table id="popup_text" style="width: 100%"></table>
					</div>
				</div></div></div>
				<div class="bl"><div class="br"><div class="bc"></div></div></div>
			</div>
		</div>
	</div></div></div>
	<div class="popbox_bl"><div class="popbox_br"><div class="popbox_bc"></div></div></div>
</div>
<div class="contentBox">
  <div class="cbox_tl"><div class="cbox_tr"><div class="cbox_tc">
    <div class="heading icon_information"><?php echo $heading_title; ?></div>
	 <div class="toolbar">
		<?php if ( !empty ($help_url) ) : ?>
	        <div class="help_element"><a href="<?php echo $help_url; ?>" target="new"><img src="<?php echo $template_dir; ?>image/icons/help.png"/></a></div>
	    <?php endif; ?>
    </div>
  </div></div></div>
  <div class="cbox_cl"><div class="cbox_cr"><div class="cbox_cc">
    <?php echo $listing_grid; ?>
  </div></div></div>
  <div class="cbox_bl"><div class="cbox_br"><div class="cbox_bc"></div></div></div>
</div>

<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/jquery/ui/ui.dialog.js"></script>
<script type="text/javascript">
<!--

var $aPopup = $('#aPopup');
var msg_id;
function show_popup(id){
	var $aPopup = $('#aPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		dialogClass: 'aPopup',
		width: 550,
		minWidth: 550,
		resize: function(event, ui){
		},
		close: function(event, ui) {
			$(this).dialog('destroy');
		}
	});

	$aPopup.removeClass('popbox popbox2');

	$.ajax({
		url: '<?php echo $popup_action; ?>',
		type: 'GET',
		dataType: 'text',
		data: 'dataset_id='+id,
		success: function(data) {
			$aPopup.addClass("popbox2");
			$('#popup_text').html(data);
			$aPopup.dialog('open');
		}
	});
}
-->
</script>