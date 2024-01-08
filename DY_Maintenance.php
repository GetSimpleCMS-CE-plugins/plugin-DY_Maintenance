<?php
/*
Plugin Name: DY Maintenance
Description: Enabling maintenance mode of the website
Version: 1.0
Author: Dmitry Yakovlev
Author URI: http://dimayakovlev.ru/

Version history:

1.0 - 29/10/2015
  - Initial version
*/

$thisfile = basename(__FILE__, '.php');

i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');

register_plugin(
  $thisfile,
  'DY Maintenance',
  '1.0',
  'Dmitry Yakovlev',
  'http://dimayakovlev.ru',
  i18n_r($thisfile . '/DESCRIPTION'),
  '',
  ''
);

add_action('settings-website-extras', 'dyMaintenanceExtras');
add_action('settings-website', 'dyMaintenanceSaveExtras');
add_action('header', 'dyMaintenanceNotify');
add_action('index-pretemplate', 'dyMaintenance');

function dyMaintenance() {
  global $dataw;
  global $TEMPLATE;
  global $USR;
  if ((string)$dataw->maintenance == '1' && $USR == null) {
    $protocol = ('HTTP/1.1' == $_SERVER['SERVER_PROTOCOL']) ? 'HTTP/1.1' : 'HTTP/1.0';
    header($protocol . ' 503 Service Unavailable', true, 503);
    header('Retry-After: 3600');
    if (is_readable($maintenance_template = GSTHEMESPATH . $TEMPLATE . '/maintenance.php')) {
      include_once $maintenance_template;
    } else {
?>
<!DOCTYPE html>
<html lang="<?php echo get_site_lang(true); ?>">
  <head>
    <meta charset="utf-8">
    <title><?php get_site_name(); ?></title>
  </head>
  <body>
    <div><?php echo strip_decode($dataw->maintenance_message); ?></div>
  </body>
</html>
<?php
    }
    die;
  }
}

function dyMaintenanceNotify() {
  $dataw = getXML(GSDATAOTHERPATH . 'website.xml');
  if ((string)$dataw->maintenance == '1') {
    $msg = json_encode(i18n_r('DY_Maintenance/NOTIFICATION_MESSAGE'));
?>
<script type="text/javascript">
  $(function() {
    $('div.bodycontent').before('<div class="error maintenance-notify" style="display:block;">'+<?php echo $msg; ?>+'</div>');
    $(".maintenance-notify").fadeOut(500).fadeIn(500);
  });
</script>
<?php
  }
}

function dyMaintenanceExtras() {
  $dataw = getXML(GSDATAOTHERPATH . 'website.xml');
?>
<div class="section" id="maintenance">
  <p class="inline">
    <input type="checkbox" name="maintenance" value="1"<?php echo $dataw->maintenance ? ' checked="checked"' : ''; ?>>
    <label for="maintenance"><?php i18n('DY_Maintenance/MAINTENANCE_SWITCH_LABEL'); ?></label>
  </p>
  <p>
    <label for="maintenance_message"><?php i18n('DY_Maintenance/MAINTENANCE_TEXT_LABEL'); ?>:</label>
    <textarea name="maintenance_message" class="text short charlimit" style="height: 62px;"<?php if ($dataw->maintenance) echo ' required';?>><?php echo strip_decode($dataw->maintenance_message); ?></textarea>
  </p>
</div>
<script>
  $(document).ready(function() {
    $('input[name="maintenance"]').click(function() {
      $('textarea[name="maintenance_message"]').prop('required', $(this).prop('checked'));
    });
  });
</script>
<?php
}

function dyMaintenanceSaveExtras() {
  global $xmls;
  if (isset($_POST['maintenance'])) $xmls->addChild('maintenance', '1');
  if (isset($_POST['maintenance_message'])) $xmls->addChild('maintenance_message')->addCData(safe_slash_html($_POST['maintenance_message']));
}