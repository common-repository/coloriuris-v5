<?php

function coloriuris_v5_options_save() {
	global $current_user;
	if (isset($_GET['apiKey']) && $_GET['apiKey'] != '') {
		$api_key = $_GET['apiKey'];
		if (coloriuris_v5_test($api_key)) {
			coloriuris_v5_user_update($current_user->ID, $api_key);
			$msg = 'Registro de los nuevos posts activado';
		} else {
			$msg .= 'Ocurrio un error al registrar el blog';
		}
		wp_redirect(admin_url('options-general.php?page=coloriuris-v5&msg=' . urlencode($msg)));
	}
	if (isset($_GET['deactivate'])) {
		coloriuris_v5_user_delete($current_user->ID);
		$msg = 'Desactivado';
		wp_redirect(admin_url('options-general.php?page=coloriuris-v5&msg=' . urlencode($msg)));
	}
	return true;
}
add_action('admin_init', 'coloriuris_v5_options_save');

function coloriuris_v5_conf() {
	if (isset($_GET['msg'])) {
		echo '<div id="message" class="updated fade"><p><strong>' . htmlentities($_GET['msg']) . '</strong></p></div>';
	}
	global $current_user;
	$api_key = coloriuris_wp_user_get($current_user->ID);
?>
<style type="text/css">
#coloriuris h2 {
	background: url(<?php echo COLORIURIS_V5_LOGO;?>) center left no-repeat;
	padding-left: 32px;
}
</style>
<div id="coloriuris" class="wrap">

<h2><?php _e('ColorIURIS - Derechos de autor', 'coloriuris'); ?> 
	- <?php echo $current_user->display_name;?>
</h2>

<?php
if ($api_key == '') {
$siteurl = get_option('siteurl');
$actionPostLoginParams = 'url=' . $siteurl;
$actionPostLoginParams = urlencode($actionPostLoginParams);
?>
<h3>
Inicie sesión con su cuenta de Coloriuris
</h3>
<iframe id="iframe-login" src="<?php echo COLORIURIS_V5_URL;?>/usuarios/login/iframe-externo?actionPostLogin=UsuariosBlogs.registrar&actionPostLoginParams=<?php echo $actionPostLoginParams;?>" width="100%" height="310px" frameborder="0">
	<a href="<?php echo COLORIURIS_V5_URL;?>/usuarios/login/iframe">
		Login
	</a>
</iframe>
<?php } else { 

$colores = array(
	'rojo' => 'Rojo',
	'amarillo' => 'Amarillo',
	'original' => 'Original'
);

?>
Registro de posts para el usuario <?php echo $current_user->display_name;?> automatizado con coloriuris.

<form method="post">
	<select id="color" name="color">
	<?php foreach ($colores as $color_id => $color_etiqueta) { ?>
		<option value="<?php echo $color_id;?>"><?php echo $color_etiqueta;?></option>
	<?php } ?>
	</select>
	<input type="submit" value="Cambiar color"/>
</form>

<a href="<?php echo admin_url('options-general.php?page=coloriuris-v5&deactivate=true');?>">desactivar</a>


<?php } ?>

<p>
	<?php _e('Más información en', 'coloriuris');?>
	<a href="https://www.coloriuris.net">https://www.coloriuris.net</a>
</p>

</div>

<?php
}
