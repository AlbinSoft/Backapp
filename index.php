<?

	include('ssi/common.php');

	$db = \EngineFwk\DB::getInstance();

	$assets = \EngineFwk\Assets::getInstance();
	$assets->add_css('common.css');
	$assets->add_js('common.js');

	if(ACTION=='set_places') {
		$places = $_POST['places'] ?? '';
		$places = json_decode($places);
		$ids    = [];
		$resp   = [ 'ok'=>true, 'ids'=>&$ids ];
		if(!is_array($places)) $resp = [ 'ok'=>false, 'msg'=>json_last_error_msg() ];
		if(is_array($places)) foreach($places as $place) switch($place->alter) {
			case 'ins':
				$db->insert('t_places', [
					'name'     => $place->name     ?? null,
					'location' => $place->location ?? null,
					'device'   => $place->device   ?? null,
					'drive'    => $place->drive    ?? null,
					'path'     => $place->path     ?? null,
					'notes'    => $place->notes    ?? null,
				]);
				$ids[$place->id_place] = $db->insert_id();
			break;
			case 'upd':
				$db->uidata('t_places', [
					'name'     => $place->name     ?? null,
					'location' => $place->location ?? null,
					'device'   => $place->device   ?? null,
					'drive'    => $place->drive    ?? null,
					'path'     => $place->path     ?? null,
					'notes'    => $place->notes    ?? null,
				], [
					'id_place' => $place->id_place
				]);
			break;
			case 'del':
				$db->delete('t_places', [
					'id_place' => $place->id_place
				]);
			break;
		}
		die(json_encode($resp));
	}

	if(ACTION=='set_backups') {
		$backups = $_POST['backups'] ?? '';
		$backups = json_decode($backups);
		$ids     = [];
		$resp    = [ 'ok'=>true, 'ids'=>&$ids ];
		if(!is_array($backups)) $resp = [ 'ok'=>false, 'msg'=>json_last_error_msg() ];
		if(is_array($backups)) foreach($backups as $backup) switch($backup->alter) {
			case 'ins':
				$db->insert('t_relations', [
					'name'         => $backup->name         ?? null,
					'id_place_src' => $backup->id_place_src ?? 0,
					'id_place_trg' => $backup->id_place_trg ?? 0,
					'agent'        => $backup->agent        ?? null,
					'frequency'    => $backup->frequency    ?? null,
					'notes'        => $backup->notes        ?? null,
				]);
				$ids[$backup->id_relation] = $db->insert_id();
			break;
			case 'upd':
				$db->uidata('t_relations', [
					'name'         => $backup->name         ?? null,
					'id_place_src' => $backup->id_place_src ?? 0,
					'id_place_trg' => $backup->id_place_trg ?? 0,
					'agent'        => $backup->agent        ?? null,
					'frequency'    => $backup->frequency    ?? null,
					'notes'        => $backup->notes        ?? null,
				], [
					'id_relation' => $backup->id_relation
				]);
			break;
			case 'del':
				$db->delete('t_relations', [
					'id_relation' => $backup->id_relation
				]);
			break;
		}
		die(json_encode($resp));
	}

	$places  = $db->get_krows("SELECT * FROM t_places WHERE 1 ");
	$backups = $db->get_krows("
		SELECT
			r.id_relation,
			r.name,
			r.id_place_src,
			r.id_place_trg,
			r.agent,
			r.frequency,
			r.notes,
			psrc.name      name_src,
			psrc.location  location_src,
			psrc.device    device_src,
			psrc.drive     drive_src,
			psrc.path      path_src,
			ptrg.name      name_trg,
			ptrg.location  location_trg,
			ptrg.device    device_trg,
			ptrg.drive     drive_trg,
			ptrg.path      path_trg,
			1
		FROM t_relations r
		JOIN t_places psrc ON r.id_place_src=psrc.id_place
		JOIN t_places ptrg ON r.id_place_trg=ptrg.id_place
		WHERE 1
	");

	foreach($places as &$place) {
	//	$place->id_place = (int) $place->id_place;
		$place->tags = $db->get_values("SELECT tag FROM t_places_tags WHERE id_place={$place->id_place}");
		$place->alter = false;
		unset($place);
	}

	foreach($backups as &$backup) {
	//	$backup->id_relation  = (int) $backup->id_relation;
	//	$backup->id_place_src = (int) $backup->id_place_src;
	//	$backup->id_place_trg = (int) $backup->id_place_trg;
		$backup->alter = false;
		unset($backup);
	}

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
	<title>Home <?=BRAND_TITLE ?></title>
	<link rel="icon" href="<?=URL_ROOT ?>favicon.ico" type="image/x-icon" />
	<!-- SusiPlate header -->
</head>
<body data-urlbase="<?=URL_ROOT ?>">

	<div id="react-root" class="board"></div>

<!-- SusiPlate footer -->
<script type="text/javascript">
var jsonPlaces  = <?=json_encode($places)  ?>;
var jsonBackups = <?=json_encode($backups) ?>;
var initPlaces  = new Map();
var initBackups = new Map();
var fback;

for(id in jsonPlaces) initPlaces.set(id, jsonPlaces[id]);
for(id in jsonBackups) initBackups.set(id, jsonBackups[id]);

import('<?=url('index.js') ?>');
</script>

</body>
</html>